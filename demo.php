<?php
require_once __DIR__ . '/src/Client.php';

$client = new \Xiaosongshu\Animation\Client(0, 0, 1);

/** 立方体 */
$config2 = [
    /** 初始三维倾斜度 */
    'angleX' => 0,
    'angleY' => 0,
    'angleZ' => 0,
    /** 三维角速度 */
    'angleStepX' => 0.1,
    'angleStepY' => 0.1,
    'angleStepZ' => 0.1,
    /** 缩放比例 */
    'scale' => 0,
    /** 初始二维偏移量 */
    'distanceX' => 0,
    'distanceY' => 0,
    /** 二维图像偏移步长 */
    'distanceXStep' => 0,
    'distanceYStep' => 0,
    /** 二维x轴正方向偏移 */
    'directionX' => 0,
    /** 二维y轴正方向偏移 */
    'directionY' => 0,
    /** 三维图案顶点坐标 : 更新三维物体的顶点坐标，可以实现模型的形状改变 ，但是不建议这样操作，因为数据量太大了 */
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
    /** 自定义三维图像顶点变化函数，此处仅为示例，请根据实际情况设置符合你自己需求的顶点变化函数，没有则不写，如果需要动态修改三维物体形状，建议使用自定义函数 */
    'function' => function (&$vertices,$index) {

    }
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
    'snowCount' => 100,
    /** 是否随机颜色 */
    'randomColor' => true,
];
/** 添加雪花飘落背景 */
$client->addSnow($config4);

/** 运行脚本 */
$client->run();