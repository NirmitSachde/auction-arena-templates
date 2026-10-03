@php
    /*
     | Auction Arena story card data. Every field the card shows comes from $aa,
     | so this block is the only place that knows about the models.
     | CHECK each source against the player-card controller: the names below
     | follow the YouTube overlay ($tournament, $player) plus the sold record.
     */
    $aaInr = function ($n) {
        if (!is_numeric($n)) return $n;
        $n = (string) (int) round($n);
        $last3 = substr($n, -3);
        $rest = substr($n, 0, -3);
        return ($rest === '' ? '' : preg_replace('/\B(?=(\d{2})+$)/', ',', $rest) . ',') . $last3;
    };
    $aaTeam = $team ?? null; // CHECK: the buying team
    $aa = [
        'tournament.name' => $tournament->name ?? '',
        'tournament.logo' => $tournament->tournament_image ?? asset('assets/img/logo/favicon.png'), // CHECK column name
        'brand.logo' => asset('assets/img/logo/logo-white.png'),
        'player.name' => trim($player->first_name . ' ' . $player->last_name),
        'player.first' => $player->first_name,
        'player.last' => $player->last_name,
        'player.role' => $player->player_speciality ?? '',
        'player.photo' => $player->player_image ?? asset('assets/users/imgs/unknown-player.png'),
        'player.base' => $aaInr($basePrice ?? '-'), // CHECK
        'bid.points' => $aaInr($soldPrice ?? '-'), // CHECK: final bid points
        'team.name' => $aaTeam->name ?? '',
        'team.logo' => $aaTeam->team_image ?? asset('assets/users/imgs/default-team.png'),
        'team.color' => $aaTeam->color ?? '#e11d48', // CHECK: team colour column, if there is one
    ];
@endphp
