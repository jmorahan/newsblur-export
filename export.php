<?php

/**
 * Copyright 2015 John Morahan.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;

class NewsBlurClient {
  public function __construct($base_url, $username = '', $password = '') {
    $this->client = new Client([
      'base_url' => $base_url,
      'defaults' => [ 'cookies' => TRUE ],
    ]);
    $this->username = $username;
    $this->password = $password;
    $this->authenticated = FALSE;
  }
  public function get($path, $query = NULL) {
    if (isset($query)) {
      $response = $this->client->get($path, [ 'query' => $query ]);
    }
    else {
      $response = $this->client->get($path);
    }
    return $response->json();
  }
  public function post($path, $body) {
    $response = $this->client->post($path, [ 'body' => $body ]);
    return $response->json();
  }

  // Subclasses can override this to display or log messages.
  protected function notify($message) {
    return;
  }

  public function login($username = NULL, $password = NULL) {
    if (isset($username)) {
      $this->username = $username;
    }
    if (isset($password)) {
      $this->password = $password;
    }
    if (!$this->authenticated) {
      $this->notify(sprintf("Logging in as '%s'...", $this->username));
      $result = $this->post('/api/login', [
        'username' => $this->username,
        'password' => $this->password,
      ]);
      $this->authenticated = $result['authenticated'];
    }
    if (!$this->authenticated) {
      $this->notify("Unable to log in. Check your username and password in 'newsblur.ini'.");
    }
    return $this->authenticated;
  }
}

class NewsBlurSavedStories extends NewsBlurClient {
  public function downloadSavedStories() {
    if (!$this->login()) {
      return FALSE;
    }
    $this->notify('Downloading saved stories...');
    $stories = [];
    $profiles = [];
    $feeds = [];
    // Start at page 1, and continue until no stories are returned.
    for ($i = 1; ($result = $this->get('/reader/starred_stories', [ 'page' => $i ])), count($result['stories']); ++$i) {
      $stories = array_merge($stories, $result['stories']);

      // Profiles aren't keyed by id, so we need to do some work to deduplicate them.
      if (!empty($result['user_profiles'])) {
        foreach ($result['user_profiles'] as $profile) {
          $profiles[$profile['user_id']] = $profile;
        }
      }

      // Feeds are already keyed by id.
      if (!empty($result['feeds'])) {
        $feeds += $result['feeds'];
      }

      $this->notify(sprintf('Downloaded %d stories', count($stories)));
    }
    return [
      'stories' => $stories,
      'feeds' => $feeds,
      'user_profiles' => array_values($profiles),
    ];
  }
}

class VerboseNewsBlurSavedStories extends NewsBlurSavedStories {
  protected function notify($message) {
    echo $message . "\n";
  }
}

// Load username, password and API endpoint from configuration file.
$ini_file = dirname(__FILE__) . '/newsblur.ini';
if (file_exists($ini_file)) {
  extract(parse_ini_file(dirname(__FILE__) . '/newsblur.ini'));
}
else {
  echo "Copy newsblur.ini.example to newsblur.ini and set your username and password.\n";
  exit;
}

if ($username == 'your username here' && $password == 'password') {
  echo "Please set your username and password in newsblur.ini\n";
  exit;
}

$client = new VerboseNewsBlurSavedStories($endpoint, $username, $password);

$export = $client->downloadSavedStories();
if ($export !== FALSE) {
  echo "Saving to starred_stories.json\n";
  $json = json_encode($export);
  file_put_contents('starred_stories.json', $json);
}
