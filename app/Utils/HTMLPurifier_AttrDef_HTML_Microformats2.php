<?php
namespace App\Utils;

/**
 * Allows Microformats2 classes but rejects any others
 */
class HTMLPurifier_AttrDef_HTML_Microformats2 extends \HTMLPurifier_AttrDef_HTML_Nmtokens
{
    /**
     * @param string $string
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return bool|string
     */
    protected function split($string, $config, $context)
    {
        // really, this twiddle should be lazy loaded
        $name = $config->getDefinition('HTML')->doctype->name;
        if ($name == "XHTML 1.1" || $name == "XHTML 2.0") {
            return parent::split($string, $config, $context);
        } else {
            return preg_split('/\s+/', $string);
        }
    }

    /**
     * @param array $tokens
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return array
     */
    protected function filter($tokens, $config, $context)
    {
        $ret = array();
        foreach ($tokens as $token) {
            if(preg_match('/^([hpue]|dt)-[a-z\-]+$/', $token)) {
                $ret[] = $token;
            }
        }
        return $ret;
    }
}
