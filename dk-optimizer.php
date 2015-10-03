<?php
/**
 * A brute force approach to building the best fantasy team for sites like DraftKings
 **/

// define constants
define('POSITION', 0);
define('NAME', 1);
define('SALARY', 2);
define('POINTS', 3);
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
$threshold = 200;
$depth = 1;

// load player data
if (file('data.csv') !== FALSE) {
  $players = array_map('str_getcsv', file('data.csv'));
} else {
  exit('Invalid data.');
}
$players = array_slice($players, 1); // remove header row

// accept command-line arguments
if (count($argv) > 1) {
  $args = array_slice($argv, 1);
  list($threshold, $depth) = $args;
}

// remove all players with 0 projected points
$trimmed_players = array();
foreach ($players as $value) {
  if ($value[POINTS] != 0) {
    $trimmed_players[] = $value;
  }
}
$players = $trimmed_players;

// trim list of players according to depth
if ($depth > 0) {
  $trimmed_players = array();
  $position_counts = array(
    QB => 0,
    RB => 0,
    WR => 0,
    TE => 0,
    DEF => 0,
  );

  // sort player list by projected points (highest first)
  usort(
    $players,
    function($a, $b) {
      if ($a[POINTS] == $b[POINTS]) return 0;
      return ($a[POINTS] > $b[POINTS]) ? -1 : 1;
    }
  );

  // determine whether we skip this player
  foreach ($players as $value) {
    $skip = FALSE;
    switch($value[POSITION]) {
      case QB:
      case TE:
      case DEF:
        if ($position_counts[$value[POSITION]] == $depth * 5) {
          $skip = TRUE;
        }
        break;
      case RB:
      case WR:
        if ($position_counts[$value[POSITION]] == $depth * 10) {
          $skip = TRUE;
        }
        break;
    }

    if (!$skip) {
      $position_counts[$value[POSITION]]++;
      $trimmed_players[] = $value;
    }
  }
  $players = $trimmed_players;
}

// run the engine
$time_start = microtime_float();
$i = 0;
while ($i < $threshold) {
  // initialize parameters
  $closed_positions = array();
  $current_team = array(
    'total_points' => 0,
    'total_salary' => 0,
  );
  $current_limits = $limits;
  $current_players = array();

  // shuffle player 'cards'
  foreach ($players as $value) {
    $current_players[sha1(microtime(TRUE) . mt_rand(10000, 90000))] = $value;
  }
  ksort($current_players);

  // draft a team
  foreach ($current_players as $value) {
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
    $i = round($i / 2);
    echo '+';
  } else {
    $i++;
  }

  if ($i % round($threshold / 10) == 0) {
    echo $i / round($threshold / 10);
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

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
