<?php

/**
 * Returns an array of people given an SQL query. This is a private helper
 * method only for this file.
 * @param $sortBy An integer specifying the sort order we need.
 * @param $sql The sql query.
 * @return Array
 * @private
 */
function getCdepSorted($sortBy, $order, $count, $party=-1) {
  switch ($sortBy) {
    case 0: $sort = 'display_name'; break;
    case 1: $sort = 'college'; break;
    case 2: $sort = 'party_name, percent'; break;
    case 3: $sort = 'percent'; break;
    case 4: $sort = 'maverick'; break;
    default: $sort = 'percent'; break;
  }
  $partyFilter = $party > 0 ? "WHERE parties.id = {$party}" : "";

  $sql = mysql_query("
    SELECT
        p.display_name, p.name, agg.percent, agg.maverick, agg.idperson,
        parties.name AS party_name, parties.id AS party_id,
        agg.possible, r.college
    FROM cdep_2008_votes_agg AS agg
    LEFT JOIN people AS p ON p.id = agg.idperson
    LEFT JOIN cdep_2008_belong_agg AS party ON party.idperson = agg.idperson
    LEFT JOIN parties ON parties.id = party.idparty
    LEFT JOIN people_history AS h
      ON h.idperson = agg.idperson AND what = 'catavencu/2008'
    LEFT JOIN results_2008_candidates AS r
      ON r.idperson = agg.idperson
    {$partyFilter}
    ORDER BY {$sort} {$order} LIMIT 0, {$count}");

  $people = array();
  while ($r = mysql_fetch_array($sql)) {
    $guy = $r;
    $guy['name'] = str_replace(' ', '+', $r['name']);
    $guy['percent'] = 100 * $r['percent'];
    $guy['maverick'] = 100 * $r['maverick'];
    $guy['anti_maverick'] = 100 - 100 * $r['maverick'];
    $guy['left_percent'] = 99 - 100 * $r['percent'];
    $guy['tiny_photo'] = getTinyImgUrl($r['idperson']);
    $guy['reversed_name'] = moveFirstNameLast($r['display_name']);

    // TODO(vivi) In a different sql, get this guys history and stick it in an
    // array. Based on that, I should display stuff on the summary page.
    $people[] = $guy;
  }
  return $people;
}


/**
 * Returns an array with the most recent votes in the database.
 * @return array
 * @private
 */
function getCdepVotes($order, $count, $from, $uid=0, $q='') {
  if ($q == '') {
    $sql = mysql_query("
      SELECT link, type, description, time, vda, vnu, vab, vmi, id
      FROM cdep_2008_votes_details
      ORDER BY time {$order}
      LIMIT {$from}, {$count}
    ");
  } else {
    $sql = mysql_query("
      SELECT link, type, description, time, vda, vnu, vab, vmi, id
      FROM cdep_2008_votes_details
      WHERE link LIKE '%{$q}%'
      ORDER BY time {$order}
      LIMIT {$from}, {$count}
    ");
  }
  $votes = array();
  while ($r = mysql_fetch_array($sql)) {
    $vote = $r;
    $vote['subject'] = str_replace("/pls/proiecte",
                                   "http://www.cdep.ro/pls/proiecte",
                                   $r['description']);
    $vote['time'] = $r['time'] / 1000;

    if ($uid != 0) {
      $vote['tags'] = getVoteTags('cdep_2008_votes_details', $r['id'], $uid);
    }

    $votes[] = $vote;
  }
  return $votes;
}


?>
