<?php
require_once __DIR__ . '/src/Client.php';

$client = new \Xiaosongshu\Animation\Client();
/** 金字塔 */
$config1 = [
    /** 三维初始倾斜角度 */
    'angleX' => 0,
    'angleY' => 0,
    'angleZ' => 0,
    /** 三维角速度 */
    'angleStepX' => 0.01,
    'angleStepY' => 0.1,
    'angleStepZ' => 0.01,
    /** 缩放比例 */
    'scale' => 0,
    /** 初始二维偏移量 */
    'distanceX' => 0,
    'distanceY' => 0,
    /** 二维图像偏移步长 */
    'distanceXStep' => 1,
    'distanceYStep' => 1,
    /** 三维物体的顶点 */
    'vertices' => [
        /** 第一个动作 */
        [
            [-1, -1, -1],
            [1, -1, -1],
            [1, 1, -1],
            [-1, 1, -1],
            [0, 0, 1]
        ],
    ],
    /** 三维物体绘图路径 */
    'edges' => [
        [0, 1], [1, 2], [2, 3], [3, 0], [0, 4], [1, 4], [2, 4], [3, 4]
    ],
    /** 二维x轴正方向偏移 */
    'directionX' => 1,
    /** 二维y轴正方向偏移 */
    'directionY' => 1,
];
$client->add3dAnimation($config1);

/** 立方体 */
$config2 = [
    /** 初始三维倾斜度 */
    'angleX' => 0,
    'angleY' => 0,
    'angleZ' => 0,
    /** 三维角速度 */
    'angleStepX' => 0.01,
    'angleStepY' => 0.01,
    'angleStepZ' => 0.01,
    /** 缩放比例 */
    'scale' => 0,
    /** 初始二维偏移量 */
    'distanceX' => 0,
    'distanceY' => 0,
    /** 二维图像偏移步长 */
    'distanceXStep' => 1,
    'distanceYStep' => 1,
    /** 二维x轴正方向偏移 */
    'directionX' => 1,
    /** 二维y轴正方向偏移 */
    'directionY' => -1,
    /** 三维图案顶点坐标 */
    'vertices' => [
        /** 第一个动作 */
        [
            [-1, -1, -1],
            [1, -1, -1],
            [1, 1, -1],
            [-1, 1, -1],
            [-1, -1, 1],
            [1, -1, 1],
            [1, 1, 1],
            [-1, 1, 1]
        ],
    ],
    /** 三维图案绘图路径 */
    'edges' => [[0, 1], [1, 2], [2, 3], [3, 0], [4, 5], [5, 6], [6, 7], [7, 4], [0, 4], [1, 5], [2, 6], [3, 7]],
];
$client->add3dAnimation($config2);
/** 流星 */
$config3 = [
    'maxStars' => 100,
    'numStars' => 10,
    'isWaterLine' => true,
    /** 初始二维偏移量 */
    'distanceX' => 0,
    'distanceY' => 0,
    /** 二维图像偏移步长 */
    'distanceXStep' => 2,
    'distanceYStep' => 1,
    /** 二维x轴正方向偏移 */
    'directionX' => 0,
    /** 二维y轴正方向偏移 */
    'directionY' => 0,
];
//$client->addStarRain($config3);

/** 添加雪花 */
$config4 = [
    /** 雪花密度 数值越大雪花越密 */
    'snowCount'=>100,
    /** 是否随机颜色雪花 */
    'random_color'=>true
];
//$client->addSnow($config4);
$client->run();