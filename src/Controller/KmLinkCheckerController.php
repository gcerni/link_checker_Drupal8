<?php

namespace Drupal\link_checker\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Node\book;
use Drupal\paragraphs\Entity\Paragraph;

class LinkCheckerController
{
  /**
   * Link checker.
   */
  public function linkChecker() {
    $query = \Drupal::service('entity.query');
    $ids = $query->get('paragraph')
      ->condition('type', 'section')
      ->execute();
    foreach ($ids as $id) {
      $p = Paragraph::load($id);
      if ($p->getType() == 'section') {
        $a[] = $p->get('field_section_body')->getString();
      }
    }
    $text = implode($a);

    // define table header
    $header = array(t('Link'), t('Code Result'));

    // generate paged table rows
    $link = $this->checkPage($text);
    $results = $this->curlCheck($link);
    $rows = array();
    foreach ($this->myCombinedArrays($link, $results) as $address => $row) {
      $rows[] = array($address, t('@term', array('@term' => $row)),);
    }
    //drupal_set_message(t('Test result: @results', array('@results' => var_export($rows, TRUE))));

    // render output
    $output['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    $output['pager'] = array(
      '#type' => 'pager'
    );
    return $output;
  }
  
   /**
   * Check page.
   */
  private function checkPage($content) {
    $textLen = strlen($content);
    $links = array();
    if ($textLen > 5) {
      $startPos = 0;
      $valid = true;
      while ($valid) {
        $spos = strpos($content, '<a ', $startPos);
        if ($spos < $startPos) $valid = false;
        $spos = strpos($content, 'href', $spos);
        $spos = strpos($content, '"', $spos) + 1;
        $epos = strpos($content, '"', $spos);
        $startPos = $epos;
        $link = substr($content, $spos, $epos - $spos);
        if (strpos($link, 'https://') !== false) $links[] = $link;
        if (strpos($link, 'http://') !== false) $links[] = $link;
      }
    }
    return $links;
  }

  /**
   * cURL check.
   */
  private function curlCheck($linksfound) {
    $linkstoPrint = array();
    foreach ($linksfound as $link) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $link);
      curl_setopt($ch, CURLOPT_HEADER, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $data = curl_exec($ch);
      $headers = curl_getinfo($ch);
      curl_close($ch);
      $linkstoPrint[] = $headers['http_code'];
    }
    return $linkstoPrint;
  }

  /**
   * My combined arrays.
   */
  private function myCombinedArrays($keys, $values) {
    foreach ($values as $index => $value) {
      yield $keys[$index] => $value;
    }
  }
}

