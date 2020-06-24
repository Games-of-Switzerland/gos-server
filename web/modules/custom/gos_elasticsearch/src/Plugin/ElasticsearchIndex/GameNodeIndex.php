<?php

namespace Drupal\gos_elasticsearch\Plugin\ElasticsearchIndex;

/**
 * A Node-Game content index class.
 *
 * @ElasticsearchIndex(
 *     id="gos_index_node_game",
 *     label=@Translation("Game Node Index"),
 *     indexName="{index_prefix}_gos_node_game_{langcode}",
 *     typeName="node",
 *     entityType="node"
 * )
 */
class GameNodeIndex extends NodeIndexBase {

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    // Create one index per language, so that we can have different analyzers.
    parent::setup();

    // Create one index per language, so that we can have different analyzers.
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      $index_name = $this->getIndexName(['langcode' => $langcode]);

      // Close the index before setting configuration.
      $this->client->indices()->close(['index' => $index_name]);

      $settings = [
        'index' => $this->indexNamePattern(),
        'body' => [
          'analysis' => [
            'filter' => [
              'english_stop' => [
                'type' => 'stop',
                'stopwords' => '_english_',
              ],
              'english_stemmer' => [
                'type' => 'stemmer',
                'language' => 'english',
              ],
              'english_possessive_stemmer' => [
                'type' => 'stemmer',
                'language' => 'possessive_english',
              ],
              'synonym_platform_filter' => [
                'type' => 'synonym_graph',
                'synonyms_path' => 'analysis/synonym_platform.txt',
              ],
            ],
            'analyzer' => [
              'ngram_gametitle_analyzer' => [
                'tokenizer' => 'ngram_gametitle_tokenizer',
                'filter' => ['lowercase'],
              ],
              'ngram_gametitle_analyzer_search' => [
                'tokenizer' => 'lowercase',
              ],
              'english_language_analyzer' => [
                'tokenizer' => 'standard',
                'filter' => [
                  'english_possessive_stemmer',
                  'lowercase',
                  'english_stop',
                  'english_stemmer',
                ],
              ],
              'synonym_platform_analyzer' => [
                'tokenizer' => 'standard',
                'filter' => [
                  'standard',
                  'lowercase',
                  'synonym_platform_filter',
                ],
              ],
            ],
            'tokenizer' => [
              'ngram_gametitle_tokenizer' => [
                'type' => 'edge_ngram',
                'min_gram' => 2,
                'max_gram' => 10,
                'token_chars' => [
                  'letter',
                  'digit',
                ],
              ],
            ],
          ],
        ],
      ];
      $this->client->indices()->putSettings($settings);

      $mapping = [
        'index' => $this->indexNamePattern(),
        'type' => $this->typeNamePattern(),
        'body' => [
          'properties' => [
            'uuid' => [
              'type' => 'keyword',
            ],
            'is_published' => [
              'type' => 'boolean',
            ],
            'title' => [
              'type' => 'text',
              'analyzer' => 'ngram_gametitle_analyzer',
              'search_analyzer' => 'ngram_gametitle_analyzer_search',
              'fields' => [
                'keyword' => [
                  'type' => 'keyword',
                ],
              ],
            ],
            'desc' => [
              'type' => 'text',
              'analyzer' => 'english_language_analyzer',
            ],
            'path' => [
              'type' => 'text',
              'index' => FALSE,
            ],
            'bundle' => [
              'type' => 'keyword',
            ],
            'releases' => [
              'type' => 'nested',
              'dynamic' => FALSE,
              'properties' => [
                'date' => [
                  'type' => 'date',
                  'format' => 'yyyy-MM-dd',
                ],
                'platform_slug' => [
                  'type' => 'keyword',
                ],
              ],
            ],
            'studios' => [
              'dynamic' => FALSE,
              'type' => 'nested',
              'properties' => [
                'name' => [
                  'type' => 'text',
                  'fields' => [
                    'raw' => [
                      'type' => 'keyword',
                    ],
                  ],
                ],
                'uuid' => [
                  'type' => 'keyword',
                ],
              ],
            ],
            'genres' => [
              'dynamic' => FALSE,
              'type' => 'nested',
              'properties' => [
                'slug' => [
                  'type' => 'keyword',
                ],
              ],
            ],
            'locations' => [
              'dynamic' => FALSE,
              'type' => 'nested',
              'properties' => [
                'slug' => [
                  'type' => 'keyword',
                ],
              ],
            ],
            'stores' => [
              'dynamic' => FALSE,
              'type' => 'nested',
              'properties' => [
                'slug' => [
                  'type' => 'keyword',
                ],
                'link' => [
                  'type' => 'text',
                  'index' => FALSE,
                ],
              ],
            ],
            'changed' => [
              'type' => 'date',
              'format' => 'epoch_second',
            ],
          ],
        ],
      ];
      $this->client->indices()->putMapping($mapping);

      // Re-open the index to make to expose it.
      $this->client->indices()->open(['index' => $index_name]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress InvalidArgument
   */
  public function index($source): void {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $source;

    // Only Index Game.
    if ($entity->bundle() !== 'game') {
      return;
    }

    parent::index($entity);
  }

}
