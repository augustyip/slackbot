<?php

date_default_timezone_set('Asia/Hong_Kong');

require __DIR__ . '/vendor/autoload.php';

$gitlab_token = 'xxxxxx';
$gitlab_project_id = 123456;
$slack_token = 'xxxxxxx';



$loop = React\EventLoop\Factory::create();

$gitlab_client = new \Gitlab\Client('https://gitlab.com/api/v3/');
$gitlab_client->authenticate($gitlab_token, \Gitlab\Client::AUTH_URL_TOKEN);


$slack_client = new Slack\RealTimeClient($loop);
$slack_client->setToken($slack_token);

// listen message event
$slack_client->on('message', function ($data) use ($slack_client, $gitlab_client, $gitlab_project_id) {
    echo "\n";

    if (!isset($data['bot_id']) && preg_match_all('/#(\d+)/', $data['text'], $matches) !== false){
      foreach ($matches[1] as $issue_id) {

        $issue = $gitlab_client->api('issues')->show($gitlab_project_id, $issue_id);
        if (!empty($issue)) {
          $issue = reset($issue);

          $slack_client->getChannelById($data['channel'])->then(function (\Slack\Channel $channel) use ($slack_client, $issue) {

              $fields = array(
                new Slack\Message\AttachmentField('Title1', 'Text', false),
              );
              $message = $slack_client->getMessageBuilder()
                ->setText('Issue *#' . $issue['iid'] . '* from MDM Development / day-one-v-one')
                ->setChannel($channel)
                ->addAttachment(new Slack\Message\Attachment('<' . $issue['web_url'] . '|' . $issue['title'] . '>', $issue['description'], null, '36a64f', $fields))
                ->create();

              $slack_client->postMessage($message);
              echo "message sent.\n";

          });

        }
      }
    }
});

$slack_client->connect()->then(function () {
    echo "Connected!\n";
});

$loop->run();
