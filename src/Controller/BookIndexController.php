<?php

namespace Drupal\BookIndex\Controller;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

include_once("sites/all/libraries/simple_html_dom/simple_html_dom.php");


/**
 * Defines HelloController class.
 */
class BookIndexController extends ControllerBase {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The node view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;
  
  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;
  
  /**
   * Constructs a BookIndexController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BookManagerInterface $book_manager, RendererInterface $renderer) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->viewBuilder = $entity_type_manager->getViewBuilder('node');
    $this->bookManager = $book_manager;
    $this->renderer = $renderer;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('book.manager'),
      $container->get('renderer')
    );
  }
  
  
  /**
   * Generates indexes of a book page and its children.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return array
   *   Return markup array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function content(NodeInterface $node) {
    $content = "Notabook! ";
    if (isset($node->book)) {
      $tree = $this->bookManager->bookSubtreeData($node->book);
            
      $contents = $this->indexTraverse($tree, [$this, 'indexNodeExport']);
      
      $exported_book = [
        '#theme' => 'book_export_html',
        '#title' => $node->label(),
        '#contents' => $contents,
        '#depth' => $node->book['depth'],
        '#cache' => [
          'tags' => $node->getEntityType()->getListCacheTags(),
        ],
      ];
      
      
      $contents = new Response($this->renderer->renderRoot($exported_book));
     

      $index = $this->getindex($contents, "index");
          
      // calculate number of links to decide where to break off the column, count initials double as these take up two lines
      $num_items = count($index) * 2;
      $initials = array_keys($index);
      foreach ($initials as $i) {
        $num_items += count($index[$i]);
      }
    
      // create page output
      $text = "<p>";
    
      // start with an # A - Z
      foreach (array("#","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z") as $initial) {
        if (array_key_exists($initial, $index)) {
          $text .= "<strong><a href = \"#" . $initial . "\">" . $initial . "</a></strong> ";
        }
        else {
          $text .= $initial;
        }
        if ($initial != "Z") {
          $text .= " - ";
        }
      }
    
      $text .= "</p>";
    
      $text .= "<table><tr style = \"background: inherit\"><td width = \"50%\" valign = \"top\">";
    
      // iterate over $index and print links
      $item = 0;
      foreach ($initials as $i) {
        $terms = array_keys($index[$i]);
      
        // Header and named anchor for new cap.
        $text .= "<a name = \"". $i . "\"></a><p><strong>" . $i . "</strong><br>\n";
      
        foreach ($terms as $t) {
          $text .= "<a href = \"" . $index[$i][$t][1] . "\">" . $index[$i][$t][0] . "</a><br>\n";
        }
        $text .= "</p>\n";
      
        // new column?
        $before = $item;
        $item += count($terms) + 2;
      
        if (($before < $num_items/2) and ($item >= $num_items/2)) {
          $text .= "</td><td width = \"50%\" valign = \"top\">";
        }
      }
    
      $text .= "</td></tr></table>";
          
     $content = $text;
    }
    
    return array(
      '#type' => 'markup',
      '#markup' => $this->t($content),
    );
  }
  
    /**
   * Traverses the book tree to build printable or exportable output.
   *
   * During the traversal, the callback is applied to each node and is called
   * recursively for each child of the node (in weight, title order).
   *
   * @param array $tree
   *   A subtree of the book menu hierarchy, rooted at the current page.
   * @param callable $callable
   *   A callback to be called upon visiting a node in the tree.
   *
   * @return string
   *   The output generated in visiting each node.
   */
  protected function indexTraverse(array $tree, $callable) {
    // If there is no valid callable, use the default callback.
    $callable = !empty($callable) ? $callable : [$this, 'indexNodeExport'];

    $build = [];
    foreach ($tree as $data) {
      // Note- access checking is already performed when building the tree.
      if ($node = $this->nodeStorage->load($data['link']['nid'])) {
        $children = $data['below'] ? $this->indexTraverse($data['below'], $callable) : '';
        $build[] = call_user_func($callable, $node, $children);
      }
    }

    return $build;
  }
  
    /**
   * Generates printer-friendly HTML for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that will be output.
   * @param string $children
   *   (optional) All the rendered child nodes within the current node. Defaults
   *   to an empty string.
   *
   * @return array
   *   A render array for the exported HTML of a given node.
   *
   * @see \Drupal\book\BookExport::exportTraverse()
   */
  protected function indexNodeExport(NodeInterface $node, $children = '') {
    $build = $this->viewBuilder->view($node, 'print', NULL);
    unset($build['#theme']);
    
    return [
      '#theme' => 'book_node_export_html',
      '#content' => $build,
      '#node' => $node,
      '#children' => $children,
    ];
  }

  /**
   * Retrieves all named anchors from the text and returns an array of the index terms and links
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

  function getindex($content, $prefix) {
    $html = str_get_html($content);
    $anchors_raw = $html->find('a[name]');
    $anchors = array();
    
    foreach ($anchors_raw as $a) {
      $link = $a->name;
      
      // these anchors are internal, so look for the nodeid
      $p = $a->parent;
      while($p) {
        if ($p->tag == "article") {
          $nid = substr($p->id, 5);
          global $base_url;
          $link = $base_url . "/node/$nid#" . $link;
          break;
        }
        $p = $p->parent;
      }
      
      
      // only interested if it's an index anchor. Please note we use links including the URL
      if (!preg_match("/#" . $prefix . "/", $link)) { continue; }
    
      # get the label and initial
      $label = preg_replace("/.+#" . $prefix . "/" , "", $link);
      $initial = strtoupper(substr($label, 0, 1));
      if (is_numeric($initial)) { $initial = "#"; }
    
      if (isset($anchors[$initial])) {
        array_push($anchors[$initial], array($label, $link));
      }
      else {
        $anchors[$initial] = array();
        array_push($anchors[$initial], array($label, $link));
      }
    }
  
    // sort on initial and term
    ksort($anchors);
    $initials = array_keys($anchors);
    foreach ($initials as $i) {
      $terms = array_keys($anchors[$i]);
      foreach ($terms as $t) {
        usort($anchors[$i], [$this, "cmp_terms"]);
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
 
  function cmp_terms($a, $b) {
    if ($a[0] == $b[0]) { return 0; }
    return ($a[0] < $b[0]) ? -1 : 1;
  }
}
