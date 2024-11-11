<?php

namespace Drupal\bookindex\Controller;

use Drupal\book\BookExport;
use Drupal\bookindex\BookIndex;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Load Simple HTML DOM
 *
 * TODO: This library should probably be included using composer but it's too
 * much for now to get all that up and running so do it the old fashioned way.
 *
 * get it here: https://simplehtmldom.sourceforge.io/
 * save it to: sites/all/libraries/simle_html_dom/
 */

include_once('sites/all/libraries/simple_html_dom/simple_html_dom.php');


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

  /** The indexer
   *
   * @var \Drupal\bookindex\BookIndex
   */

  protected $bookIndex;

  /**
   * Constructs a BookIndexController object.
   *
   * @param \Drupal\book\BookExport $bookExport
   *   The book export service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\bookindex\Bookindex $bookIndex
   *   The book converter.
   */
  public function __construct(BookExport $bookExport, RendererInterface $renderer, BookIndex $bookIndex) {
    $this->bookExport = $bookExport;
    $this->renderer = $renderer;
    $this->bookIndex = $bookIndex;
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
        '#markup' => $this->t("Not a book page, so no index will be made."),
      );
    }
  
    // Grab the contents of the book in HTML form
    $exported_book = $this->bookExport->bookExportHtml($node);
    $contents = new Response($this->renderer->renderRoot($exported_book));
     
    // Filter out the named anchors that should be in the index
    $index = $this->bookIndex->bookindex_getindex($contents, 'index');
          
    // calculate number of links to decide where to break off the column
    // count initials double as these take up two lines
    $num_items = count($index) * 2;
    $initials = array_keys($index);
    foreach ($initials as $i) {
      $num_items += count($index[$i]);
    }
    
    // create page content
    $content = "<p>\n";
    
    // start with an # A - Z
    foreach (array_merge(['#'], range('A','Z')) as $initial) {
      if (array_key_exists($initial, $index)) {
        $content .= "  <strong><a href = '#" . $initial . "'>";
        $content .= $initial;
        $content .= "</a></strong>";
      }
      else {
        $content .= "  "  . $initial;
      }
      if ($initial != 'Z') {
        $content .= " - \n";
      }
    }
    
    $content .= "\n</p>\n";
    $content .= "<table>\n  <tr style = 'background: inherit'>\n";
    $content .= "<td width = '50%' valign = 'top'>\n";
    
    // iterate over $index and print links
    $item = 0;
    foreach ($initials as $i) {
      $terms = array_keys($index[$i]);
      
      // Header and named anchor for new cap.
      $content .= "      <p>\n";
      $content .= "<a name = '". $i . "'></a>";
      $content .= "<strong>" . $i . "</strong><br>\n";
      
      foreach ($terms as $t) {
        $content .= "        <a href = '" . $index[$i][$t][1] . "'>";
        $content .= $index[$i][$t][0];
        $content .= "</a><br>\n";
      }
      $content .= "      </p>\n";
    
      // new column?
      $before = $item;
      $item += count($terms) + 2;
    
      if (($before < $num_items/2) and ($item >= $num_items/2)) {
        $content .= "    </td>\n    <td width = '50%' valign = 'top'>\n";
      }
    }    
    $content .= "    </td>\n  </tr>\n</table>\n";
       
    return array(
      '#type' => 'markup',
      '#markup' => $this->t($content),
    );
  }
}
