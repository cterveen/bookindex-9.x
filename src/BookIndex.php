<?php

namespace Drupal\bookindex;

class BookIndex {
  // Variables to be used within the class.

  public function __construct() {
    /*
     * Load the HTML parser
     *
     * get it here: https://simplehtmldom.sourceforge.io/
     * save it to: /libraries/simle_html_dom/
     * (only needs simple_html_dom.php)
     */
    include_once(DRUPAL_ROOT . '/libraries/simple_html_dom/simple_html_dom.php');
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

  public function bookindex_getindex($content, $prefix) {
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
