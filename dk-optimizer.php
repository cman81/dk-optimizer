<?php
/**
 * A brute force approach to building the best fantasy team for sites like DraftKings
 **/

// define constants
define('POSITION', 0);
define('NAME', 1);
define('SALARY', 2);
define('POINTS', 3);
define('UUID', 4);
define('QB', 'QB');
define('RB', 'RB');
define('WR', 'WR');
define('TE', 'TE');
define('FLEX', 'Flex');
define('DEF', 'DST');

// initialize parameters
$limits = array();
$best_team = array('total_points' => 0);
$limits = array(
  QB => 1,
  RB => 2,
  WR => 3,
  TE => 1,
  FLEX => 1,
  DEF => 1,
  'salary' => 50000,
);
$iterations = 30000;

// load player data
if (file('data.csv') !== FALSE) {
  $players = array_map('str_getcsv', file('data.csv'));
} else {
  // sample data
  $players = array(
    array('Player Name', 'Position', 'Salary', 'Projected Points'),
    array('Julio Jones', 'WR', '9300', '12.9'),
    array('Andre Johnson', 'WR', '9300', '12.9'),
    array('Blargh', 'WR', '9300', '12.9'),
    array('Next', 'WR', '9300', '12.9'),
    array('Hello', 'WR', '9300', '12.9'),
  );
}
$players = array_slice($players, 1); // remove header row

// accept command-line arguments
if (count($argv) > 1) {
  $args = array_slice($argv, 1);
  list($iterations) = $args;
}

// run the engine
$time_start = microtime_float();
for ($i = 0; $i < $iterations; $i++) {
  // initialize parameters
  $closed_positions = array();
  $current_team = array(
    'total_points' => 0,
    'total_salary' => 0,
  );
  $current_limits = $limits;

  // shuffle player 'cards'
  foreach ($players as $key => $value) {
    $players[$key][UUID] = generate_uuid();
  }
  usort(
    $players,
    function($a, $b) {
      if ($a[UUID] == $b[UUID]) return 0;
      return ($a[UUID] > $b[UUID]) ? 1 : -1;
    }
  );
  
  // draft a team
  foreach ($players as $value) {
    // break out...
    if (count($closed_positions) == 6) { // ...if we have a complete team
      break;
    }
    if ($current_team['total_salary'] == $current_limits['salary']) { // ...if we have hit the salary cap
      break;
    }

    // does adding this player put us over the salary cap?
    if (($current_team['total_salary'] + $value[SALARY]) > $current_limits['salary']) {
      continue;
    }
    
    // do we have a position slot for him?
    if ($current_limits[$value[POSITION]]) { // e.g.: if we draw a QB, check against $limits[QB]
      $current_limits[$value[POSITION]]--;
      if ($current_limits[$value[POSITION]] == 0) {
        $closed_positions[] = $value[POSITION];
      }
    } elseif ($current_limits[FLEX] && in_array($value[POSITION], array(RB, WR, TE))) { // do we have a flex slot?
      $current_limits[FLEX]--;
      if ($current_limits[FLEX] == 0) {
        $closed_positions[] = FLEX;
      }
    } else {
      continue;
    }
    
    // add to our team
    $current_team['players'][] = $value;
    $current_team['total_points'] += $value[POINTS];
    $current_team['total_salary'] += $value[SALARY];
  }

  // is this team the best we have assembled?
  if ($current_team['total_points'] > $best_team['total_points']) {
    $best_team = $current_team;
    echo '+';
  }
}
$time_end = microtime_float();
$time = $time_end - $time_start;
echo "\n";

usort(
  $best_team['players'],
  function($a, $b) {
    if ($a[POINTS] == $b[POINTS]) {
      return ($a[SALARY] > $b[SALARY]) ? -1 : 1;
    }
    return ($a[POINTS] > $b[POINTS]) ? -1 : 1;
  }
);

$output = array();
foreach ($best_team['players'] as $value) {
  echo str_pad($value[NAME] . ' ', 25) . str_pad('(' . $value[POSITION] . ')', 5) . ' cost = ' . str_pad('$' . $value[SALARY], 6) . ' points = ' . str_pad($value[POINTS], 4) . "\n";
  $output[] = $value[NAME];
}

echo "Total Salary: $" . $best_team['total_salary'] . "\n";
$output[] = $best_team['total_salary'];

echo "Total Points: " . $best_team['total_points'] . "\n";
$output[] = $best_team['total_points'];

echo "Execution time: " . $time . " seconds\n";
$file = fopen('output.csv', 'a');
fwrite($file, implode(',', $output) . "\n");
fclose($file);

/**
 * http://rogerstringer.com/2013/11/15/generate-uuids-php
 **/
function generate_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
