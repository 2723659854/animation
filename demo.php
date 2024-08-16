<?php
require_once __DIR__ . '/src/Client.php';

$client = new \Xiaosongshu\Animation\Client();

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
    'directionX' => -1,
    /** 二维y轴正方向偏移 */
    'directionY' => -1,
    /** 三维图案顶点坐标 : 更新三维物体的顶点坐标，可以实现模型的形状改变 */
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
        // ... 其他动作
    ],
    /** 三维图案绘图路径 */
    'edges' => [[0, 1], [1, 2], [2, 3], [3, 0], [4, 5], [5, 6], [6, 7], [7, 4], [0, 4], [1, 5], [2, 6], [3, 7]],
];

//$client->add3dAnimation($config2);

/** 流星 */
$config3 = [
    'maxStars' => 10,
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

/** 雪花飘落背景 */
$config4 = [
    /** 雪花密度 */
    'snowCount'=>100,
    /** 是否随机颜色 */
    'randomColor'=>true,
];
/** 添加雪花飘落背景 */
$client->addSnow($config4);

/** 运行脚本 */
$client->run();