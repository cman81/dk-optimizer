<?php

// define constants
define('NAME', 0);
define('POSITION', 1);
define('SALARY', 2);
define('POINTS', 3);
define('UUID', 4);
define('QB', 'QB');
define('RB', 'RB');
define('WR', 'WR');
define('TE', 'TE');
define('FLEX', 'Flex');
define('DEF', 'DEF');

// initialize parameters
$limits = array();
$best_team = array('points' => 0);

// load player data
$players = array(
  array('Player Name', 'Position', 'Salary', 'Projected Points'),
  array('Julio Jones', 'WR', '9300', '12.9'),
  array('Andre Johnson', 'WR', '9300', '12.9'),
  array('Blargh', 'WR', '9300', '12.9'),
  array('Next', 'WR', '9300', '12.9'),
  array('Hello', 'WR', '9300', '12.9'),
);

// accept command-line arguments
if (count($argv > 1)) {
  $args = array_slice($argv, 1);
  list($limits[QB], $limits[RB], $limits[WR], $limits[TE], $limits[FLEX], $limits[DEF], $limits['salary'], $iterations) = $args;
} else {
  $limits = array(
    QB => 1,
    RB => 2,
    WR => 3,
    TE => 1,
    FLEX => 1,
    DEF => 1,
    'salary' => 50000,
  );
  $iterations = 100;
}

// initialize teams

// run the engine
for ($i = 0; $i < $iterations; $i++) {
  // initialize parameters
  $closed_positions = array();
  $current_team = array();
  $current_limits = $limits;

  // shuffle player 'cards'
  foreach ($players as $key => $value) {
    $players[UUID] = generate_uuid();
  }
  usort(
    $players,
    function player_sort($a, $b) {
      if ($a[UUID] == $b[UUID]) return 0;
      return ($a[UUID] > $b[UUID]) ? 1 : -1;
    }
  );
  
  // draft a team
  foreach ($players as $value) {
    // break out if we have a complete team
    if (count($closed_positons == 6) {
      break;
    }
    
  // draft a player if we have a slot for him
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
      // skip this card
      continue;
    }
    
    // add to our team
    $current_team['players'][] = $value;
    $current_team['points'] += $value[POINTS];
    $current_limits['salary'] -= $value[SALARY];
    
    // check against our salary cap
    if ($current_limits['salary'] < 0) {
      break;
    }
  }
  
  // is this team the best we have assembled?
  if ($current_team['points'] > $best_team['points']) {
    $best_team = $current_team;
  }
  
  // show we have completed one iteration
  echo '.';
}

echo "\n";
var_dump($best_team);
