<?php

/*
 * Plugin Name: Amazon CloudFront and Cloudflare SSL
 * Plugin URI: https://github.com/adampatterson/wordpress-amazon-cloudfront-ssl
 * Description: Amazon CloudFront SSL for WordPress
 * Version: 1.0.0
 * Text Domain: amazon-cloudfront-ssl
 * Author: Adam Patterson
 * Author URI: https://www.adampatterson.ca
 */

/**
* Modified version of https://wordpress.org/plugins/cloudflare-flexible-ssl/
*/
class AmazonCloudFrontCloudflareSsl
{

    public function run()
    {

        $serverOptions = array('HTTP_CF_VISITOR', 'HTTP_X_FORWARDED_PROTO', 'HTTP_CLOUDFRONT_FORWARDED_PROTO');
        foreach ($serverOptions as $option) {

            if (isset($_SERVER[$option]) && (strpos($_SERVER[$option], 'https') !== false)) {
                $_SERVER['HTTPS'] = 'on';
                break;
            }
        }

        if (is_admin()) {
            add_action('admin_init', array($this, 'maintainPluginLoadPosition'));
        }
    }

    /**
     * Sets this plugin to be the first loaded of all the plugins.
     */
    public function maintainPluginLoadPosition()
    {
        $baseFile       = plugin_basename(__FILE__);
        $pluginPosition = $this->getActivePluginLoadPosition($baseFile);
        if ($pluginPosition > 1) {
            $this->setActivePluginLoadPosition($baseFile, 0);
        }
    }

    /**
     * @param string $pluginFile
     *
     * @return int
     */
    public function getActivePluginLoadPosition($pluginFile)
    {
        $optionKey = is_multisite() ? 'active_sitewide_plugins' : 'active_plugins';
        $active    = get_option($optionKey);
        $position  = -1;
        if (is_array($active)) {
            $position = array_search($pluginFile, $active);
            if ($position === false) {
                $position = -1;
            }
        }

        return $position;
    }

    /**
     * @param string $pluginFile
     * @param int $newPosition
     */
    public function setActivePluginLoadPosition($pluginFile, $newPosition = 0)
    {

        $active = $this->setArrayValueToPosition(get_option('active_plugins'), $pluginFile, $newPosition);
        update_option('active_plugins', $active);

        if (is_multisite()) {
            $active = $this->setArrayValueToPosition(get_option('active_sitewide_plugins'), $pluginFile, $newPosition);
            update_option('active_sitewide_plugins', $active);
        }
    }

    /**
     * @param $subjectArray
     * @param $value
     * @param $newPosition
     *
     * @return array
     */
    public function setArrayValueToPosition($subjectArray, $value, $newPosition)
    {

        if ($newPosition < 0 || ! is_array($subjectArray)) {
            return $subjectArray;
        }

        $maxPosition = count($subjectArray) - 1;
        if ($newPosition > $maxPosition) {
            $newPosition = $maxPosition;
        }

        $position = array_search($value, $subjectArray);
        if ($position !== false && $position != $newPosition) {

            // remove existing and reset index
            unset($subjectArray[$position]);
            $subjectArray = array_values($subjectArray);

            // insert and update
            // http://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
            array_splice($subjectArray, $newPosition, 0, $value);
        }

        return $subjectArray;
    }
}

$amazonCheck = new AmazonCloudFrontCloudflareSsl();
$amazonCheck->run();
