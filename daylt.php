<?php

/**
 *
 * @link              https://day.lt/
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Day.lt names
 * Description:       Displays list of today’s names from day.lt
 * Version:           1.0.0
 * Author:            Aivaras Karaliūnas
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       daylt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class scrapeDaylt
{
    public function __construct()
    {
        $this->buildList();
        $this->createShortcode();
    }

    private function getNames()
    {
        // delete_transient( 'daylt_names' ); // Can be used to clear transient
        $cachedNames = get_transient( 'daylt_names' );
        $names = [];

        if ($cachedNames) {
            $names = $cachedNames;
        } else {
            $scrapedNames = $this->scrapeDayltNames();
            if ($scrapedNames) {
                $names = $scrapedNames;
                set_transient( 'daylt_names', $names, 3600 ); // Adds names to cache for 1 hour
            }
        }


        return $names;
    }

    private function scrapeDayltNames()
    {
        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0',
                'Accept'=> '*/*',
                'Accept-Language'=> 'en-US,en;q=0.5',
                'Accept-Encoding'=> 'gzip, deflate, br',
                'Connection'=> 'keep-alive'
            ]
        ]);

        $response = $client->get('https://day.lt');
        $body = (string) $response->getBody(true);

        $crawler = new Crawler($body);
        $filter = '.vardadieniai a';

        $names = $crawler->filter($filter)->each(function (Crawler $item) {
            return $item->text();
        });
        unset($crawler);
        
        if (!$names) {
            return null;
        }

        return $names;
    }

    public function buildList()
    {
        $names = $this->getNames();

        $output = '<ul>';
        foreach ($names as $name) {
            $output .= '<li>' . $name . '</li>';
        }
        $output .= '</ul>';

        return $output;
    }

    private function createShortcode()
    {
        add_shortcode('daylt_names', [$this, 'buildList']);
    }
}

new scrapeDaylt();
