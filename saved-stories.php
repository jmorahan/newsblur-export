<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

extract(parse_ini_file('newsblur.ini'));

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

  public function login($username = NULL, $password = NULL) {
    if (isset($username)) {
      $this->username = $username;
    }
    if (isset($password)) {
      $this->password = $password;
    }
    if (!$this->authenticated) {
      $result = $this->post('/api/login', [
        'username' => $this->username,
        'password' => $this->password,
      ]);
      $this->authenticated = $result['authenticated'];
    }
  }
}

class NewsBlurSavedStories extends NewsBlurClient {
  public function downloadSavedStories() {
    $this->login();
    $stories = array();
    $profiles = array();
    $feeds = array();
    for ($i = 1; ($result = $this->get('/reader/starred_stories', [ 'page' => $i ])), count($result['stories']); ++$i) {
      $stories = array_merge($stories, $result['stories']);
      if (!empty($result['user_profiles'])) {
        foreach ($result['user_profiles'] as $profile) {
          $profiles[$profile['user_id']] = $profile;
        }
      }
      if (!empty($result['feeds'])) {
        $feeds += $result['feeds'];
      }
    }
    return [
      'stories' => $stories,
      'feeds' => $feeds,
      'user_profiles' => array_values($profiles),
    ];
  }
}
$client = new NewsBlurSavedStories($endpoint, $username, $password);
$json = json_encode($client->downloadSavedStories());
file_put_contents('saved-stories.json', $json);
