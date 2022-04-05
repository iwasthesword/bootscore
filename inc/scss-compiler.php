<?php

/**
 * Functions to compile scss in the theme itself
 *
 * @package Bootscore
 */

require_once "scssphp/scss.inc.php";

use ScssPhp\ScssPhp\Compiler;

/**
 * Compiles the scss to a css file to be read by the browser.
 */
function bootscore_compile_scss()
{
  $compiler = new Compiler();
  $compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);

  $compiler->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);

  if (bootscore_child_has_scss() && is_child_theme())
  {
    $theme_directory = get_stylesheet_directory();
  }
  else
  {
    $theme_directory = get_template_directory();
  }

  $scss_file = $theme_directory . '/scss/main.scss';
  $css_file = $theme_directory . '/css/main.css';

  $compiler->setImportPaths(dirname($scss_file));
  if (is_child_theme() && bootscore_child_has_scss())
  {
    $compiler->addImportPath(get_template_directory() . '/scss/');
  }

  $last_modified = bootscore_get_last_modified_scss($theme_directory);
  $stored_modified = get_theme_mod('bootscore_scss_modified_timestamp', 0);

  $is_environment_dev = (wp_get_environment_type() === 'development');

  if ($is_environment_dev)
  {
    $compiler->setSourceMapOptions([
      'sourceMapURL'      => site_url('', 'relative') . '/' . substr(str_replace(ABSPATH, '', $css_file), 0, -3) . 'map',
      'sourceMapBasepath' => substr(str_replace('\\', '/', ABSPATH), 0, -1),
      'sourceRoot' => site_url('', 'relative') . '/',
    ]);
  }

  try
  {
    if ($last_modified > $stored_modified || !file_exists($css_file) || $is_environment_dev)
    {
      $compiled = $compiler->compileString(file_get_contents($scss_file));

      if (!file_exists(dirname($css_file)))
      {
        mkdir(dirname($css_file), 0755, true);
      }

      file_put_contents($css_file, $compiled->getCss());
      if ($is_environment_dev)
      {
        file_put_contents(substr($css_file, 0, -3) . 'map', $compiled->getSourceMap());
      }

      set_theme_mod('bootscore_scss_modified_timestamp', $last_modified);
    }
  }
  catch (Exception $e)
  {
    wp_die('<b>bootScore SCSS Compiler - Caught exception:</b><br><br> ' . $e->getMessage());
  }
}




/**
 * Checks if the scss files and returns the last modified times added together.
 *
 * @return float Last modified times added together.
 */
function bootscore_get_last_modified_scss($theme_directory)
{
  $directory = $theme_directory . '/scss/';
  $files = scandir($directory);
  $total_last_modified = 0;
  foreach ($files as $file)
  {
    if (strpos($file, '.scss') !== false || strpos($file, '.css') !== false)
    {
      $file_stats = stat($directory . $file);
      $total_last_modified += $file_stats['mtime'];
    }
  }
  $total_last_modified += stat(get_template_directory() . '/scss/bootstrap/bootstrap.scss')['mtime'];
  return $total_last_modified;
}

/**
 * Check if the child theme has scss files included.
 *
 * @return boolean True when child theme has scss files.
 */
function bootscore_child_has_scss()
{
  return file_exists(get_stylesheet_directory() . '/scss/main.scss');
}

/**
 * Compile the css variables into theme.json for Gutenberg use
 */

function bootscore_compile_json()
{
  require __DIR__ . '/../vendor/autoload.php';

  if (bootscore_child_has_scss() && is_child_theme())
  {
    $theme_directory = get_stylesheet_directory();
  }
  else
  {
    $theme_directory = get_template_directory();
  }

  $parser = new \Sabberworm\CSS\Parser(file_get_contents($theme_directory . '/css/main.css'));
  $cssDocument = $parser->parse();
  $vars_fetch = array("--bs-primary", "--bs-secondary", "--bs-blue", "--bs-indigo", "--bs-purple", "--bs-pink", "--bs-red", "--bs-orange", "--bs-yellow", "--bs-green", "--bs-teal", "--bs-cyan", "--bs-white", "--bs-gray", "--bs-gray-dark", "--bs-body-font-family", "--bs-font-sans-serif", "--bs-font-monospace", "--bs-body-color", "--bs-body-bg");
  $rulesets = $cssDocument->getAllRuleSets();
  $custom_palette = array();

  foreach ($rulesets as $val)
  {
    $tmp = $val->getRules();
    foreach ($tmp as $rule)
    {
      $ruleV = (string) $rule->getValue();
      $ruleR = $rule->getRule();
      if (in_array($ruleR, $vars_fetch))
      {
        $custom_palette[$ruleR] = $ruleV;
      }
    }
  }

  $json_stru = array(
    "version" => 2,
    "styles" => array(
      "color" => array(
        "text" => $custom_palette["--bs-body-color"],
        "background" => $custom_palette["--bs-body-bg"]
      ),
      "typography" => array(
        "fontFamily" => "var(--wp--preset--font-family--default-font-family)",
      )
    ),
    "settings" => array(
      "typography" => array(
        "fontFamilies" => array(
          array(
            "fontFamily" => $custom_palette["--bs-body-font-family"],
            "slug" => "default-font-family",
            "name" => "Default Bootstrap Font"
          ),
          array(
            "fontFamily" => $custom_palette["--bs-font-sans-serif"],
            "slug" => "font-sans-serif",
            "name" => "Sans Serif"
          ),
          array(
            "fontFamily" => $custom_palette["--bs-font-monospace"],
            "slug" => "font-monospace",
            "name" => "Monospace"
          )
        )
      ),
      "color" => array(
        "palette" => array(
          array(
            "name" => "Primary",
            "slug" => "primary",
            "color" => $custom_palette["--bs-primary"]
          ),
          array(
            "name" => "Secondary",
            "slug" => "secondary",
            "color" => $custom_palette["--bs-secondary"]
          ),
          array(
            "name" => "Blue",
            "slug" => "blue",
            "color" => $custom_palette["--bs-blue"]
          ),
          array(
            "name" => "Indigo",
            "slug" => "indigo",
            "color" => $custom_palette["--bs-indigo"]
          ),
          array(
            "name" => "Purple",
            "slug" => "purple",
            "color" => $custom_palette["--bs-purple"]
          ),
          array(
            "name" => "Pink",
            "slug" => "pink",
            "color" => $custom_palette["--bs-pink"]
          ),
          array(
            "name" => "Red",
            "slug" => "red",
            "color" => $custom_palette["--bs-red"]
          ),
          array(
            "name" => "Orange",
            "slug" => "orange",
            "color" => $custom_palette["--bs-orange"]
          ),
          array(
            "name" => "Yellow",
            "slug" => "yellow",
            "color" => $custom_palette["--bs-yellow"]
          ),
          array(
            "name" => "Green",
            "slug" => "green",
            "color" => $custom_palette["--bs-green"]
          ),
          array(
            "name" => "Teal",
            "slug" => "teal",
            "color" => $custom_palette["--bs-teal"]
          ),
          array(
            "name" => "Cyan",
            "slug" => "cyan",
            "color" => $custom_palette["--bs-cyan"]
          ),
          array(
            "name" => "White",
            "slug" => "white",
            "color" => $custom_palette["--bs-white"]
          ),
          array(
            "name" => "Gray",
            "slug" => "gray",
            "color" => $custom_palette["--bs-gray"]
          ),
          array(
            "name" => "Gray Dark",
            "slug" => "gray-dark",
            "color" => $custom_palette["--bs-gray-dark"]
          )
        )
      )
    )
  );
  $fp = fopen(get_stylesheet_directory() . '/theme.json', 'w');
  fwrite($fp, json_encode($json_stru));
  fclose($fp);
}
