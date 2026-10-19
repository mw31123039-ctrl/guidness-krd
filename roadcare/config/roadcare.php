<?php

return [
    'cities' => ['Erbil', 'Sulaymaniyah', 'Duhok', 'Halabja'],
    'categories' => ['pothole', 'road_crack', 'surface_damage', 'speed_bump', 'drainage', 'other'],
    'severities' => ['minor', 'moderate', 'severe', 'critical'],
    'statuses' => ['submitted', 'reviewing', 'scheduled', 'in_progress', 'resolved', 'rejected'],
    'transitions' => [
        'submitted' => ['reviewing', 'rejected'],
        'reviewing' => ['scheduled', 'rejected'],
        'scheduled' => ['in_progress', 'reviewing'],
        'in_progress' => ['resolved', 'scheduled'],
        'resolved' => ['reviewing'],
        'rejected' => ['reviewing'],
    ],
];
