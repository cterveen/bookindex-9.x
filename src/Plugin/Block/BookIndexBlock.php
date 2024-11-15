<?php

/**
 * @file
 * Provides a 'Book Index' block.
 */

namespace Drupal\bookindex\Plugin\Block;

use Drupal\book\BookExport;
use Drupal\book\BookManagerInterface;
use Drupal\bookindex\BookIndex;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a 'BookIndex' block.
 *
 * @Block(
 *   id = "book_index_block",
 *   admin_label = @Translation("Book index block"),
 * )
 */
class BookIndexBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The book export service.
   *
   * @var \Drupal\book\BookExport
   */
  protected $bookExport;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /** The indexer.
   *
   * @var \Drupal\bookindex\BookIndex
   */

  protected $bookIndex;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs the BookIndexBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\book\BookExport $bookExport
   *   The book export service.
   * @param \Drupal\bookindex\Bookindex $bookIndex
   *   The book converter.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, BookManagerInterface $book_manager, EntityStorageInterface $node_storage, BookExport $book_export, RendererInterface $renderer, BookIndex $book_index) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->bookManager = $book_manager;
    $this->nodeStorage = $node_storage;
    $this->bookExport = $book_export;
    $this->renderer = $renderer;
    $this->bookIndex = $book_index;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('book.manager'),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('book.export'),
      $container->get('renderer'),
      $container->get('bookindex.index')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the root.
    $node = $this->routeMatch->getParameter('node');
    $root = Node::load($node->book['bid']);

    // Grab the contents of the book in HTML form.
    $exported_book = $this->bookExport->bookExportHtml($root);
    $contents = new Response($this->renderer->renderRoot($exported_book));

    // Filter out the named anchors that should be in the index.
    $index = $this->bookIndex->bookindex_getindex($contents, 'index');

    // Print the index.
    return array(
      '#theme' => 'bookindex-index',
      '#title' => $this->t('Index'),
      '#items' => $index,
    );
  }

}
