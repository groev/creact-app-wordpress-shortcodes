<?php

class RAS_Shortcode
{
    public function __construct()
    {
        add_shortcode('React-App', array($this, 'handle_shortcode'));
    }

    public function handle_shortcode($atts, $content = null)
    {
        // Checking shortcode attritubes
        $a = shortcode_atts(array(
            'id' => null,
            'stylesheet' => "on"
        ), $atts);

        // if the post or id does not exist, return nothing
        if (!$a['id'] || !get_post($a['id'])) {
            return;
        }

        // render shortcode
        return $this->render_app($a['id'], $a['stylesheet']);
    }

    // render_app handles to output of the shortcode.
    private function render_app($id, $stylesheet)
    {
        $path = get_post_meta($id, 'react_app_folder', true);
        $url = get_post_meta($id, 'react_app_url', true);
        if (!$path || !$url) {
            return;
        }
        $html = file_get_contents($path.'index.html');
        $parsed =  DOMDocument::loadHTML($html); // Using DOMDocument to get all HTML Tags
        $divs = $parsed->getElementsByTagName('div'); // getting all divs, one needed
        $scripts = $parsed->getElementsByTagName('script'); // getting all scripts
        $links = $parsed->getElementsByTagName('link'); // getting all links (for styling)
        // Starting output buffer
        ob_start();
        $i=0;
        // going througha all divs and echo those with id.
        if ($divs) {
            foreach ($divs as $div) {
                $divId = $div->getAttribute('id');
                if ($divId) {
                    echo '<div id="'.$divId.'"></div>';
                }
            }
        }
        // Looping through scripts
        if ($scripts) {
            foreach ($scripts as $script) {
                $src = $script->getAttribute('src');
                $content = $script->textContent;
                // if script has source, enque
                if ($src) {
                    $i++;
                    $src = str_replace('/static/', $url.'static/', $src);
                    wp_enqueue_script('react-script-'.$i, $src, array(), "1.0.0", true);
                }
                // if script has content, echo
                if ($content) {
                    $content = str_replace("'/'", "'".$url."/'", $content);
                    $content = str_replace('"/"', '"'.$url.'/"', $content);
                    $content = str_replace(site_url(), "", $content);
                    echo '<script>'.$content.'</script>';
                }
            }
        }
        if ($links && $stylesheet !== "off") {
            foreach ($links as $link) {
                // if link has rel, enque, footer position is potentially to be optimized.
                if ($link->getAttribute('rel') === "stylesheet") {
                    $href = $link->getAttribute('href');
                    $href = str_replace('/static/', $url.'static/', $href);
                    wp_enqueue_style('react-app-style', $href, array(), "1.0.0", 'all');
                }
            }
        }

        return ob_get_clean();
    }
}
new RAS_Shortcode();