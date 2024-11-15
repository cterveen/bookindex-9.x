<?php

/**
 * @file
 * Provides the 'BookIndex' class with shared functions.
 */

namespace Drupal\bookindex;

/**
 * Provides functions to retrieve the book or page index.
 */
class BookIndex {

  /**
   * Constructs the new BookIndex object.
   */
  public function __construct() {
    /*
     * Load the HTML parser
     *
     * get it here: https://simplehtmldom.sourceforge.io/
     * save simple_html_dom.php it to: /libraries/simle_html_dom/
     */
    include_once(DRUPAL_ROOT . '/libraries/simple_html_dom/simple_html_dom.php');
  }

  /**
   * Retrieves all named anchors from the text and returns an array of the
   * index terms and links.
   *
   * @param string $content
   *   The content of the book or page in html form.
   *
   * @param string $prefix
   *   A string that acts as an optional prefix for filtering anchors to be 
   *   collected.
   *
   * @return array
   *   The array consists of 27 named arrays with the items:
   *     - initial: # for non-letters and A to Z.
   *     - items: a list of anchors each in a named array with the items:
   *       - title: the term.
   *       - url: the full url to the named anchor.
   */
  public function bookindex_getindex($content, $prefix) {
    $html = str_get_html($content);
    $anchors_raw = $html->find('a[name]');
    $anchors = array();
    
    foreach ($anchors_raw as $a) {
      $link = $a->name;

      // Skip if it's not an index anchor.
      if (!preg_match('/^' . $prefix . '/', $link)) {
        continue;
      }

      // Anchors are for use within a page and have to be made into a full URL.
      // Node id can be grabed from the id attribute of the article element.
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

      // Get the label and initial.
      $label = preg_replace('/.+#' . $prefix . '/' , '', $link);
      $initial = strtoupper(substr($label, 0, 1));
      if (is_numeric($initial)) {
        $initial = '#';
      }

      if (isset($anchors[$initial])) {
        array_push($anchors[$initial], ['title' => $label, 'url' => $link]);
      }
      else {
        $anchors[$initial] = [];
        array_push($anchors[$initial], ['title' => $label, 'url' => $link]);
      }
    }

    // Sort on initial and term.
    ksort($anchors);
    $initials = array_keys($anchors);
    foreach ($initials as $i) {
      $terms = array_keys($anchors[$i]);
      foreach ($terms as $t) {
        usort($anchors[$i], [$this, 'bookindex_cmp_terms']);
      }
    }

    // Make the index.
    $index = [];
    foreach (array_merge(['#'], range('A','Z')) as $i) {
      if (array_key_exists($i, $anchors)) {
        array_push($index, ['initial' => $i, 'items' => $anchors[$i]]);
      }
      else {
        array_push($index, ['initial' => $i, 'items' => []]);
      }
    }

    return $index;
  }

  /**
   * Sorts two arrays on the title element.
   *
   * Callback for uasort() within bookindex_getindex()
   *
   * @param array $a
   *   An array with a value in $a['title'].
   * 
   * @param array $b
   *   An array with a value in $b['title'].
   *
   * @return int
   *   0 if the values of $a['title'] and $n['title'] are the same.
   *   -1 if the value of $a['title'] is (alphabetically) ordered before
   *      $b['title'].
   *   1 if the value of $a['title'] is (alphabetically) ordered after
   *      $b['title'].
   */
  private function bookindex_cmp_terms($a, $b) {
    if ($a['title'] == $b['title']) { return 0; }
    return ($a['title'] < $b['title']) ? -1 : 1;
  }

}
