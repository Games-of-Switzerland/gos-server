<?php

namespace Drupal\gos_elasticsearch\Plugin\rest\resource;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Drupal\gos_elasticsearch\Plugin\ElasticsearchIndex\GameNodeIndex;
use Drupal\gos_elasticsearch\Plugin\rest\ResourceValidator\ElasticGamesResourceValidator;
use Drupal\gos_rest\Plugin\rest\ValidatorFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Proxy to access to Elasticsearch Games Documents.
 *
 * @RestResource(
 *     id="elasticsearch_games_resource",
 *     label=@Translation("Proxy to access to Elasticsearch Games Documents"),
 *     uri_paths={
 *         "canonical": "/search/games"
 *     }
 * )
 */
class ElasticGamesResource extends ElasticResourceBase {

  /**
   * The Elasticsearch Plugin ID to be used.
   *
   * @var string
   *
   * @see \Drupal\gos_elasticsearch\Plugin\ElasticsearchIndex\MultilingualSiteWideIndex::id
   */
  public const ELASTICSEARCH_PLUGIN_ID = 'gos_index_node_game';

  /**
   * The page size.
   *
   * Use to limit the number of properties returned and optimize response size.
   *
   * @var int
   */
  public const PAGER_SIZE = 24;

  /**
   * The taxonomy term Storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress MissingParamType
   * @psalm-suppress ArgumentTypeCoercion
   * @psalm-suppress PropertyTypeCoercion
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerChannelInterface $logger,
    ValidatorFactory $validator_factory,
    ElasticsearchIndexManager $elasticsearch_plugin_manager,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $validator_factory, $elasticsearch_plugin_manager);
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress PossiblyNullArgument
   * @psalm-suppress ArgumentTypeCoercion
   * @psalm-suppress PossiblyNullReference
   * @psalm-suppress UnsafeInstantiation
   * @psalm-suppress PossiblyInvalidArgument
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('gos_rest.validator_factory'),
      $container->get('plugin.manager.elasticsearch_index.processor'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Proxy to fetch content on Elasticsearch.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The Json response.
   */
  public function get(Request $request): CacheableJsonResponse {
    // Setup the base response & cacheable-metadata.
    parent::get($request);

    $resource_validator = $this->buildResourceValidator($request);

    // None valid resource parameters will answer immediately with errors msg.
    if (!$this->isValid($resource_validator)) {
      return $this->buildValidatorErrorResponse($resource_validator);
    }

    /** @var \Drupal\gos_elasticsearch\Plugin\ElasticsearchIndex\GameNodeIndex $index */
    $index = $this->elasticsearchPluginManager->createInstance(self::ELASTICSEARCH_PLUGIN_ID);
    $es_query = $this->buildBaseGamesElasticsearchQuery($index, $resource_validator);

    // When no parameters (excepted pager) have been submitted,
    // then the order must be random.
    // This is intended to be used on Homepage only.
    if ($resource_validator->getRaw() === ['page' => '0']) {
      $es_query['body']['sort'] = [
        '_script' => [
          'script' => 'Math.random()',
          'type' => 'number',
          'order' => 'asc',
        ],
      ];
    }

    // Add the sort property.
    if (!empty($resource_validator->getSort())) {
      $es_query['body']['sort'] = $this->addSort($resource_validator->getSort());
    }

    $search = (string) $request->query->get('q', '');
    $search = Xss::filter($search);

    if ($search) {
      // Filter the "hits" by given game title.
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);

      // Filter the "aggregations" by given game titles.
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_release_years_histogram']['filter']['bool']['must'][] = $this->addFullTextGameTitleCondition($search);
    }

    $platforms = $resource_validator->getPlatforms();

    if ($platforms !== NULL && !empty($platforms)) {
      // Filter the "hits" by given platform(s).
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addPlatformsFilter($platforms);

      // Filter the "aggregations" by given platform(s).
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addPlatformsFilter($platforms);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addPlatformsFilter($platforms);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addPlatformsFilter($platforms);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addPlatformsFilter($platforms);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addPlatformsFilter($platforms);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_release_years_histogram']['filter']['bool']['must'][] = $this->addPlatformsFilter($platforms);
    }

    $genres = $resource_validator->getGenres();

    if ($genres !== NULL && !empty($genres)) {
      // Filter the "hits" by given genre(s).
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addGenresFilter($genres);

      // Filter the "aggregations" by given genre(s).
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addGenresFilter($genres);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addGenresFilter($genres);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addGenresFilter($genres);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addGenresFilter($genres);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addGenresFilter($genres);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_release_years_histogram']['filter']['bool']['must'][] = $this->addGenresFilter($genres);
    }

    $stores = $resource_validator->getStores();

    if ($stores !== NULL && !empty($stores)) {
      // Filter the "hits" by given store(s).
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addStoresFilter($stores);

      // Filter the "aggregations" by given store(s).
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addStoresFilter($stores);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addStoresFilter($stores);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addStoresFilter($stores);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addStoresFilter($stores);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addStoresFilter($stores);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_release_years_histogram']['filter']['bool']['must'][] = $this->addStoresFilter($stores);
    }

    $locations = $resource_validator->getLocations();

    if ($locations !== NULL && !empty($locations)) {
      // Filter the "hits" by given location(s).
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addLocationsFilter($locations);

      // Filter the "aggregations" by given location(s).
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addLocationsFilter($locations);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addLocationsFilter($locations);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addLocationsFilter($locations);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addLocationsFilter($locations);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addLocationsFilter($locations);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_release_years_histogram']['filter']['bool']['must'][] = $this->addLocationsFilter($locations);
    }

    $cantons = $resource_validator->getCantons();

    if ($cantons !== NULL && !empty($cantons)) {
      // Filter the "hits" by given canton(s).
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addCantonsFilter($cantons);

      // Filter the "aggregations" by given canton(s).
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addCantonsFilter($cantons);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addCantonsFilter($cantons);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addCantonsFilter($cantons);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addCantonsFilter($cantons);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addCantonsFilter($cantons);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_release_years_histogram']['filter']['bool']['must'][] = $this->addCantonsFilter($cantons);
    }

    $release_year_range = $resource_validator->getReleaseYearRange();

    if (isset($release_year_range['start']) || isset($release_year_range['end'])) {
      // Filter the "hits" by given Release Year Range.
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addReleaseYearRangeFilter($release_year_range);

      // Filter the "aggregations" by given Release Year Range.
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addReleaseYearRangeFilter($release_year_range);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addReleaseYearRangeFilter($release_year_range);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addReleaseYearRangeFilter($release_year_range);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addReleaseYearRangeFilter($release_year_range);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addReleaseYearRangeFilter($release_year_range);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addReleaseYearRangeFilter($release_year_range);
    }

    $release_year = $resource_validator->getReleaseYear();

    if ($release_year !== NULL) {
      // Filter the "hits" by given Release Year.
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addReleaseYearFilter($release_year);

      // Filter the "aggregations" by given Release Year.
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addReleaseYearFilter($release_year);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addReleaseYearFilter($release_year);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addReleaseYearFilter($release_year);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_states']['filter']['bool']['must'][] = $this->addReleaseYearFilter($release_year);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addReleaseYearFilter($release_year);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addReleaseYearFilter($release_year);
    }

    $states = $resource_validator->getStates();

    if ($states !== NULL && !empty($states)) {
      // Filter the "hits" by given state(s).
      $es_query['body']['query']['bool']['filter']['bool']['must'][] = $this->addStatesFilter($states);

      // Filter the "aggregations" by given states(s).
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_platforms']['filter']['bool']['must'][] = $this->addStatesFilter($states);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_genres']['filter']['bool']['must'][] = $this->addStatesFilter($states);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_stores']['filter']['bool']['must'][] = $this->addStatesFilter($states);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_locations']['filter']['bool']['must'][] = $this->addStatesFilter($states);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_cantons']['filter']['bool']['must'][] = $this->addStatesFilter($states);
      $es_query['body']['aggregations']['aggs_all']['aggs']['all_filtered_release_years_histogram']['filter']['bool']['must'][] = $this->addStatesFilter($states);
    }

    try {
      $results = $index->search($es_query);
      $this->response->setData($results);
    }
    catch (\Exception $exception) {
      return $this->buildElasticsearchErrorResponse($exception);
    }

    $this->responseCache->setCacheMaxAge(0);
    $this->responseCache->addCacheTags([
      'node_list:game',
      'node_list:studio',
      'node_list:people',
    ]);
    $this->response->addCacheableDependency($this->responseCache);

    return $this->response;
  }

  /**
   * Build a sort parameters for query.
   *
   * @param array $sort
   *   The sort property with direction as key.
   *
   * @return array
   *   The sort elasticsearch structure.
   */
  protected function addSort(array $sort): array {
    $direction = key($sort);
    $property = $sort[$direction];

    switch ($property) {
      case 'releases.date':
        $order = [
          $property => [
            [
              'order' => key($sort),
              'nested' => ['path' => 'releases'],
              'missing' => '_last',
            ],
          ],
        ];

        break;

      default:
        $order = [
          $property => [
            ['order' => key($sort)],
          ],
        ];

        break;
    }

    return $order;
  }

  /**
   * Build resource validator from request to validate request parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Drupal\gos_elasticsearch\Plugin\rest\ResourceValidator\ElasticGamesResourceValidator
   *   Resource validator.
   */
  protected function buildResourceValidator(Request $request): ElasticGamesResourceValidator {
    $resource_validator = new ElasticGamesResourceValidator($request->query->all());
    $resource_validator->setRaw($request->query->all());

    // The platform(s) optional parameter.
    if ($request->query->has('platforms')) {
      /** @var \Drupal\taxonomy\TermInterface[] $platforms */
      $platforms = [];

      foreach ($request->query->all('platforms') as $slug) {
        $platform = $this->termStorage->loadByProperties([
          'vid' => 'platform',
          'field_slug' => $slug,
        ]);

        if (empty($platform)) {
          continue;
        }

        /** @var \Drupal\taxonomy\TermInterface $platform */
        $platform = reset($platform);
        $platforms[] = $platform;
      }

      $resource_validator->setPlatforms($platforms);
    }

    // The genre(s) optional parameter.
    if ($request->query->has('genres')) {
      /** @var \Drupal\taxonomy\TermInterface[] $genres */
      $genres = [];

      foreach ($request->query->all('genres') as $slug) {
        $genre = $this->termStorage->loadByProperties([
          'vid' => 'genre',
          'field_slug' => $slug,
        ]);

        if (empty($genre)) {
          continue;
        }

        /** @var \Drupal\taxonomy\TermInterface $genre */
        $genre = reset($genre);
        $genres[] = $genre;
      }

      $resource_validator->setGenres($genres);
    }

    // The location(s) optional parameter.
    if ($request->query->has('locations')) {
      /** @var \Drupal\taxonomy\TermInterface[] $locations */
      $locations = [];

      foreach ($request->query->all('locations') as $slug) {
        $location = $this->termStorage->loadByProperties([
          'vid' => 'location',
          'field_slug' => $slug,
        ]);

        if (empty($location)) {
          continue;
        }

        /** @var \Drupal\taxonomy\TermInterface $location */
        $location = reset($location);
        $locations[] = $location;
      }

      $resource_validator->setLocations($locations);
    }

    // The canton(s) optional parameter.
    if ($request->query->has('cantons')) {
      /** @var \Drupal\taxonomy\TermInterface[] $cantons */
      $cantons = [];

      foreach ($request->query->all('cantons') as $slug) {
        $canton = $this->termStorage->loadByProperties([
          'vid' => 'canton',
          'field_slug' => $slug,
        ]);

        if (empty($canton)) {
          continue;
        }

        /** @var \Drupal\taxonomy\TermInterface $canton */
        $canton = reset($canton);
        $cantons[] = $canton;
      }

      $resource_validator->setCantons($cantons);
    }

    return $resource_validator;
  }

  /**
   * Add a condition to filter games by canton(s) slug.
   *
   * @param \Drupal\taxonomy\TermInterface[] $cantons
   *   The collection of cantons to use for filtering.
   *
   * @return array
   *   The Nested OR-Condition query to filter-out games by canton.
   */
  private function addCantonsFilter(array $cantons): array {
    $structure = [
      'nested' => [
        'path' => 'cantons',
        'query' => [
          'bool' => [
            'should' => [],
          ],
        ],
      ],
    ];

    foreach ($cantons as $canton) {
      $structure['nested']['query']['bool']['should'][] = ['term' => ['cantons.slug' => $canton->field_slug->value]];
    }

    return $structure;
  }

  /**
   * Add a full-text condition to query.
   *
   * @param string $search
   *   The keywords to filter by.
   *
   * @return array
   *   The condition query to filter-out by keyword on content.
   */
  private function addFullTextGameTitleCondition(string $search): array {
    return [
      'multi_match' => [
        'query' => $search,
        'fields' => ['title'],
        'operator' => 'or',
        'fuzziness' => 0,
      ],
    ];
  }

  /**
   * Add a condition to filter games by genres slug.
   *
   * @param \Drupal\taxonomy\TermInterface[] $genres
   *   The collection of genres to use for filtering.
   *
   * @return array
   *   The Nested OR-Condition query to filter-out games by genre.
   */
  private function addGenresFilter(array $genres): array {
    $structure = [
      'nested' => [
        'path' => 'genres',
        'query' => [
          'bool' => [
            'should' => [],
          ],
        ],
      ],
    ];

    foreach ($genres as $genre) {
      $structure['nested']['query']['bool']['should'][] = ['term' => ['genres.slug' => $genre->field_slug->value]];
    }

    return $structure;
  }

  /**
   * Add a condition to filter games by location(s) slug.
   *
   * @param \Drupal\taxonomy\TermInterface[] $locations
   *   The collection of locations to use for filtering.
   *
   * @return array
   *   The Nested OR-Condition query to filter-out games by location.
   */
  private function addLocationsFilter(array $locations): array {
    $structure = [
      'nested' => [
        'path' => 'locations',
        'query' => [
          'bool' => [
            'should' => [],
          ],
        ],
      ],
    ];

    foreach ($locations as $location) {
      $structure['nested']['query']['bool']['should'][] = ['term' => ['locations.slug' => $location->field_slug->value]];
    }

    return $structure;
  }

  /**
   * Add a condition to filter games by platforms slug.
   *
   * @param \Drupal\taxonomy\TermInterface[] $platforms
   *   The collection of platforms to use for filtering.
   *
   * @return array
   *   The Nested OR-Condition query to filter-out games by release platform.
   */
  private function addPlatformsFilter(array $platforms): array {
    $structure = [
      'nested' => [
        'path' => 'releases',
        'query' => [
          'bool' => [
            'should' => [],
          ],
        ],
      ],
    ];

    foreach ($platforms as $platform) {
      $structure['nested']['query']['bool']['should'][] = ['term' => ['releases.platform_slug' => $platform->field_slug->value]];
    }

    return $structure;
  }

  /**
   * Add a condition to filter unpublished elements.
   *
   * @return array
   *   The Condition query to filter-out unpublished element.
   */
  private function addPublishedConditions(): array {
    return [
      'term' => [
        'is_published' => TRUE,
      ],
    ];
  }

  /**
   * Add a condition to filter games by Release Year slug.
   *
   * @param int $year
   *   The year to use for filtering.
   *
   * @return array
   *   The Nested OR-Condition query to filter-out games by release year.
   */
  private function addReleaseYearFilter(int $year): array {
    return [
      'nested' => [
        'path' => 'releases_years',
        'query' => [
          'bool' => [
            'should' => [
              [
                'term' => [
                  'releases_years.year' => ['value' => (string) $year],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Add a condition to filter games by Release Year Range.
   *
   * @param array $range
   *   The year range start|end .
   *
   * @return array
   *   The Nested Condition query to filter-out games by release year
   *   between start and end.
   */
  private function addReleaseYearRangeFilter(array $range): array {
    $filtered_range = [
      'nested' => [
        'path' => 'releases_years',
        'query' => [
          'bool' => [
            'should' => [
              'range' => [
                'releases_years.year' => [],
              ],
            ],
          ],
        ],
      ],
    ];

    if (isset($range['start']) && !empty($range['start'])) {
      $filtered_range['nested']['query']['bool']['should']['range']['releases_years.year']['gte'] = $range['start'];
    }

    if (isset($range['end']) && !empty($range['end'])) {
      $filtered_range['nested']['query']['bool']['should']['range']['releases_years.year']['lte'] = $range['end'];
    }

    return $filtered_range;
  }

  /**
   * Add a condition to filter games by states slug.
   *
   * @param array $states
   *   The collection of states slug to use for filtering.
   *
   * @return array
   *   The Nested OR-Condition query to filter-out games by states name.
   */
  private function addStatesFilter(array $states): array {
    $structure = [
      'nested' => [
        'path' => 'releases_states',
        'query' => [
          'bool' => [
            'should' => [],
          ],
        ],
      ],
    ];

    foreach ($states as $state) {
      $structure['nested']['query']['bool']['should'][] = ['term' => ['releases_states.state' => $state]];
    }

    return $structure;
  }

  /**
   * Add a condition to filter games by stores slug.
   *
   * @param array $stores
   *   The collection of stores slug to use for filtering.
   *
   * @return array
   *   The Nested OR-Condition query to filter-out games by stores name.
   */
  private function addStoresFilter(array $stores): array {
    $structure = [
      'nested' => [
        'path' => 'stores',
        'query' => [
          'bool' => [
            'should' => [],
          ],
        ],
      ],
    ];

    foreach ($stores as $store) {
      $structure['nested']['query']['bool']['should'][] = ['term' => ['stores.slug' => $store]];
    }

    return $structure;
  }

  /**
   * Build the basic (without filtering or aggs-filtered) Games ES query.
   *
   * @param \Drupal\gos_elasticsearch\Plugin\ElasticsearchIndex\GameNodeIndex $index
   *   The games index to build the query for.
   * @param \Drupal\gos_elasticsearch\Plugin\rest\ResourceValidator\ElasticGamesResourceValidator $resource_validator
   *   The games Resource validator.
   *
   * @return array
   *   The Elasticsearch skeleton query.
   */
  private function buildBaseGamesElasticsearchQuery(GameNodeIndex $index, ElasticGamesResourceValidator $resource_validator): array {
    return [
      'index' => $index->getIndexName([]),
      'from' => $resource_validator->getPage() * self::PAGER_SIZE,
      'size' => self::PAGER_SIZE,
      'body' => [
        'query' => [
          'bool' => [
            'should' => [
              // Where all the conditions modifying the Score should be added.
            ],
            'must' => [
              // Where all the conditions modifying the Score should be added.
            ],
            'filter' => [
              'bool' => [
                // Where all the conditions without a Score impact should be.
                'must' => [
                  $this->addPublishedConditions(),
                ],
              ],
            ],
          ],
        ],

        'aggregations' => [
          'aggs_all' => [
            'global' => new \stdClass(),
            'aggs' => [
              // Genres aggregations.
              'all_filtered_genres' => [
                'filter' => [
                  'bool' => [
                    // Where all the filter w/o a Score impact should be.
                    'must' => [
                      $this->addPublishedConditions(),
                    ],
                  ],
                ],
                'aggregations' => [
                  'all_nested_genres' => [
                    'nested' => [
                      'path' => 'genres',
                    ],
                    'aggs' => [
                      'genres_name_keyword' => [
                        'terms' => [
                          'field' => 'genres.slug',
                          'min_doc_count' => 0,
                          'size' => 100,
                        ],
                        'aggs' => [
                          'genres_facet_data' => [
                            'top_hits' => [
                              '_source' => [
                                'genres.name',
                              ],
                              'size' => 1,
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],

              // Locations aggregations.
              'all_filtered_locations' => [
                'filter' => [
                  'bool' => [
                    // Where all the filter w/o a Score impact should be.
                    'must' => [
                      $this->addPublishedConditions(),
                    ],
                  ],
                ],
                'aggregations' => [
                  'all_nested_locations' => [
                    'nested' => [
                      'path' => 'locations',
                    ],
                    'aggs' => [
                      'locations_name_keyword' => [
                        'terms' => [
                          'field' => 'locations.slug',
                          'min_doc_count' => 0,
                          'size' => 50,
                        ],
                        'aggs' => [
                          'locations_facet_data' => [
                            'top_hits' => [
                              '_source' => [
                                'locations.name',
                              ],
                              'size' => 1,
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],

              // Cantons aggregations.
              'all_filtered_cantons' => [
                'filter' => [
                  'bool' => [
                    // Where all the filter w/o a Score impact should be.
                    'must' => [
                      $this->addPublishedConditions(),
                    ],
                  ],
                ],
                'aggregations' => [
                  'all_nested_cantons' => [
                    'nested' => [
                      'path' => 'cantons',
                    ],
                    'aggs' => [
                      'cantons_name_keyword' => [
                        'terms' => [
                          'field' => 'cantons.slug',
                          'min_doc_count' => 0,
                          'size' => 50,
                        ],
                        'aggs' => [
                          'cantons_facet_data' => [
                            'top_hits' => [
                              '_source' => [
                                'cantons.name',
                              ],
                              'size' => 1,
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],

              // Platforms aggregations.
              'all_filtered_platforms' => [
                'filter' => [
                  'bool' => [
                    // Where all the filter w/o a Score impact should be.
                    'must' => [
                      $this->addPublishedConditions(),
                    ],
                  ],
                ],
                'aggregations' => [
                  'all_nested_platforms' => [
                    'nested' => [
                      'path' => 'releases',
                    ],
                    'aggs' => [
                      'platforms_name_keyword' => [
                        'terms' => [
                          'field' => 'releases.platform_slug',
                          'min_doc_count' => 0,
                          'size' => 100,
                        ],
                        'aggs' => [
                          'platforms_facet_data' => [
                            'top_hits' => [
                              '_source' => [
                                'releases.platform_name',
                              ],
                              'size' => 1,
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],

              // Stores aggregations.
              'all_filtered_stores' => [
                'filter' => [
                  'bool' => [
                    // Where all the filter w/o a Score impact should be.
                    'must' => [
                      $this->addPublishedConditions(),
                    ],
                  ],
                ],
                'aggregations' => [
                  'all_nested_stores' => [
                    'nested' => [
                      'path' => 'stores',
                    ],
                    'aggs' => [
                      'stores_name_keyword' => [
                        'terms' => [
                          'field' => 'stores.slug',
                          'min_doc_count' => 0,
                          'size' => 100,
                        ],
                      ],
                    ],
                  ],
                ],
              ],

              // States aggregations.
              'all_filtered_states' => [
                'filter' => [
                  'bool' => [
                    // Where all the filter w/o a Score impact should be.
                    'must' => [
                      $this->addPublishedConditions(),
                    ],
                  ],
                ],
                'aggregations' => [
                  'all_nested_states' => [
                    'nested' => [
                      'path' => 'releases_states',
                    ],
                    'aggs' => [
                      'states_name_keyword' => [
                        'terms' => [
                          'field' => 'releases_states.state',
                          'min_doc_count' => 0,
                          'size' => 10,
                        ],
                      ],
                    ],
                  ],
                ],
              ],

              // Release Histogram.
              'all_filtered_release_years_histogram' => [
                'filter' => [
                  'bool' => [
                    // Where all the filter w/o a Score impact should be.
                    'must' => [
                      $this->addPublishedConditions(),
                    ],
                  ],
                ],
                'aggregations' => [
                  'all_nested_release_years' => [
                    'nested' => [
                      'path' => 'releases_years',
                    ],
                    'aggs' => [
                      'releases_over_time' => [
                        'date_histogram' => [
                          'field' => 'releases_years.year',
                          'calendar_interval' => 'year',
                          'format' => 'yyyy',
                          'missing' => '1900',
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],

        'sort' => [
          '_score' => [
            'order' => 'desc',
          ],
        ],
      ],
    ];
  }

}
