<?php
namespace App\Utils;

use HTMLPurifier, HTMLPurifier_Config;

class HTML {

  public static function sanitizeHTML($html, $allowImg=true, $baseURL=false) {
    $allowed = [
      'a',
      'abbr',
      'b',
      'br',
      'code',
      'del',
      'em',
      'i',
      'q',
      'strike',
      'strong',
      'time',
      'blockquote',
      'pre',
      'p',
      'h1',
      'h2',
      'h3',
      'h4',
      'h5',
      'h6',
      'ul',
      'li',
      'ol',
      'span',
      'hr'
    ];
    if($allowImg)
      $allowed[] = 'img';

    $config = HTMLPurifier_Config::createDefault();
    $config->set('Cache.DefinitionImpl', null);
    $config->set('HTML.AllowedElements', $allowed);

    if($baseURL) {
      $config->set('URI.MakeAbsolute', true);
      $config->set('URI.Base', $baseURL);
    }

    $def = $config->getHTMLDefinition(true);
    $def->addElement(
      'time',
      'Inline',
      'Inline',
      'Common',
      [
        'datetime' => 'Text'
      ]
    );
    // Override the allowed classes to only support Microformats2 classes
    $def->manager->attrTypes->set('Class', new HTMLPurifier_AttrDef_HTML_Microformats2());
    $purifier = new HTMLPurifier($config);
    $sanitized = $purifier->purify($html);
    $sanitized = str_replace("&#xD;","\r",$sanitized);
    return trim($sanitized);
  }

}
