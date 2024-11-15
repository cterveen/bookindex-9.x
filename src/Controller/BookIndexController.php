<?php

/**
 * @file
 * Provides the controller for BookIndex.
 */

namespace Drupal\bookindex\Controller;

use Drupal\book\BookExport;
use Drupal\bookindex\BookIndex;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines BookIndexController class.
 */
class BookIndexController extends ControllerBase {

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
   * Constructs the BookIndexController object.
   *
   * @param \Drupal\book\BookExport $bookExport
   *   The book export service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\bookindex\Bookindex $bookIndex
   *   The book converter.
   */
  public function __construct(BookExport $book_export, RendererInterface $renderer, BookIndex $book_index) {
    $this->bookExport = $book_export;
    $this->renderer = $renderer;
    $this->bookIndex = $book_index;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.export'),
      $container->get('renderer'),
      $container->get('bookindex.index')
    );
  }

  /**
   * Generates an index of a book page and its children.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return array
   *   Return markup array.
   *
   */
  public function content(NodeInterface $node) {
    if (!isset($node->book)) {
      return array(
        '#type' => 'markup',
        '#markup' => $this->t('Not a book page, so no index will be made.'),
      );
    }
  
    // Grab the contents of the book in HTML form.
    $exported_book = $this->bookExport->bookExportHtml($node);
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
