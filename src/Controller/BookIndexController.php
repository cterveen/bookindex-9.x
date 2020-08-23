<?php

namespace Drupal\bookindex\Controller;

use Drupal\book\BookExport;
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
  
  /**
   * Constructs a BookIndexController object.
   *
   * @param \Drupal\book\BookExport $bookExport
   *   The book export service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(BookExport $bookExport, RendererInterface $renderer) {
    $this->bookExport = $bookExport;
    $this->renderer = $renderer;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.export'),
      $container->get('renderer')
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
    $index = $this->bookindex_getindex($contents, 'index');
          
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

  /**
   * Retrieves all named anchors from the text and returns an array of the
   * index terms and links
   *
   * @param $content
   *   The content of the book in html form
   *
   * @param $prefix
   *   A string that acts as an optional prefix for filtering anchors to be 
   *   collected.
   *
   * @return
   *  An array of arrays of arrays of each index item
   *  array of initials
   *    array of items
   *      array of label and link
   */

  private function bookindex_getindex($content, $prefix) {
    $html = str_get_html($content);
    $anchors_raw = $html->find('a[name]');
    $anchors = array();
    
    foreach ($anchors_raw as $a) {
      $link = $a->name;
      
      // anchors are for use within a page and have to be made into a full URL
      // node id can be grabed from the id attribute of the article element
      $p = $a->parent;
      while($p) {
        if ($p->tag == 'article') {
          $nid = substr($p->id, 5);
          global $base_url;
          $link = $base_url . '/node/' . $nid . '#' . $link;
          break;
        }
        $p = $p->parent;
      }
      
      
      // skip if it's not an index anchor
      if (!preg_match('/#' . $prefix . '/', $link)) {
        continue;
      }
    
      # get the label and initial
      $label = preg_replace('/.+#' . $prefix . '/' , '', $link);
      $initial = strtoupper(substr($label, 0, 1));
      if (is_numeric($initial)) {
        $initial = '#';
      }
    
      if (isset($anchors[$initial])) {
        array_push($anchors[$initial], [$label, $link]);
      }
      else {
        $anchors[$initial] = [];
        array_push($anchors[$initial], [$label, $link]);
      }
    }
  
    // sort on initial and term
    ksort($anchors);
    $initials = array_keys($anchors);
    foreach ($initials as $i) {
      $terms = array_keys($anchors[$i]);
      foreach ($terms as $t) {
        usort($anchors[$i], [$this, 'bookindex_cmp_terms']);
      }
    }
    
    return $anchors;
  }

  /**
   * Sorts two arrays on the first element
   * @param $a 
   *   An array of at least one value
   * 
   * @param $b
   *   The array of at least one value to compare with
   *
   * @return
   *   0 if the first items are the same
   *   -1 if the first item of $a is (alphabetically) ordered before the first item of $b
   *   1 if the first item of $a is (alphabetically) ordered after the first item of $b
   */
 
  private function bookindex_cmp_terms($a, $b) {
    if ($a[0] == $b[0]) { return 0; }
    return ($a[0] < $b[0]) ? -1 : 1;
  }
}
