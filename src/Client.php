<?php

namespace Xiaosongshu\Animation;
class Client
{


    /** 圆心 */
    private $centerX = 0; // 几何中心X
    private $centerY = 0; // 几何中心Y


    /** 轨迹画布，存储轨迹 */
    private $trail = [];
    /** 存储星星的数组 */
    private $stars = [];
    /** 终端宽度 */
    private $width = 0;
    /** 终端高度 */
    private $height = 0;
    /** 动画刷新时间 */
    private $fresh = 0;

    /**
     * 获取终端宽度和高度
     * @return array|int[]
     */
    private function getTerminalSize()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'mode con'; // Windows系统获取控制台尺寸的命令
            $output = shell_exec($cmd); // 执行命令并获取输出
            preg_match('/Columns:\s*(\d+)/', $output, $widthMatch); // 匹配列宽
            preg_match('/Lines:\s*(\d+)/', $output, $heightMatch); // 匹配行高
            $width = isset($widthMatch[1]) ? (int)$widthMatch[1] : 80; // 获取列宽
            $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : 25; // 获取行高
        } else {
            $size = [];
            if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
                $width = $size[1]; // 获取宽度
                $height = $size[2]; // 获取高度
            } else {
                $width = 80; // 默认宽度
                $height = 25; // 默认高度
            }
        }
        /** 某些操作系统返回的宽度和高度很离谱，导致画布很大，以致于看不到动画 */
        return [$width > 200 ? 200 : $width, $height > 35 ? 35 : $height]; // 返回宽度和高度
    }


    /**
     * 生成随机颜色（256色模式）
     */
    private function getRandomColor()
    {
        /** 每6个数字一个渐变色段，先生成渐变色的最亮色 */
        $colors = range(21, 231, 6); // 生成色彩范围
        $numbers = count($colors) - 1; // 色彩数量
        /** 返回一个随机的亮色 */
        return $colors[rand(0, $numbers)]; // 随机返回一个色彩
    }

    /**
     * 使用Bresenham算法绘制直线
     * @param array $canvas 画布数组，二维数组，表示终端的显示区域
     * @param int $x0 起始点的x坐标
     * @param int $y0 起始点的y坐标
     * @param int $x1 结束点的x坐标
     * @param int $y1 结束点的y坐标
     * @note 已知起点和终点，绘制线段
     * @note Bresenham算法总结起来就是，为了维持直线的倾斜度，当y方向增量过大，倾斜度被增加了，就需要增加一个x方向单位，以降低倾斜度所以要减一个y方向增量。
     * 当x方向增量过大，那么说明直线的倾斜度变小了，需要增加一个y方向的单位使得倾斜度增加，倾斜度要增加一个x方向增量。
     */
    private function drawLine(array &$canvas, int $x0, int $y0, int $x1, int $y1)
    {
        /** x方向增量 */
        $dx = abs($x1 - $x0); // x方向的距离
        /** y方向增量 */
        $dy = abs($y1 - $y0); // y方向的距离
        /** 确定坐标变化方向 增大还是减小，步长为1 */
        $sx = ($x0 < $x1) ? 1 : -1; // x方向的步长
        $sy = ($y0 < $y1) ? 1 : -1; // y方向的步长
        /** 这个实际上是斜率，值越大说明直线的倾角越大 */
        $err = $dx - $dy; // 误差值
        /** 使用点绘制线 */
        while (true) {
            // 如果坐标在画布范围内
            if ($x0 >= 0 && $x0 < count($canvas[0]) && $y0 >= 0 && $y0 < count($canvas)) {
                $lastColor = $this->getRandomColor(); // 获取随机颜色
                $string = "\033[38;5;{$lastColor}m*\033[0m"; // 生成带颜色的字符
                $canvas[$y0][$x0] = $string; // 在画布上绘制字符
            }
            /** 当绘制到终点，则不再绘制点 */
            if ($x0 == $x1 && $y0 == $y1) break; // 结束条件
            /** 计算误差值 */
            $e2 = $err * 2; // 误差值的两倍
            /** 调整x坐标: 如果误差值大于y方向增量，则需要在x方向上移动一个单位。*/
            /** 说明直线变陡了，是y方向变化过大，需要x方向补偿一个单位 以便维持斜率 ，那么斜率减一个y增量 */
            if ($e2 > -$dy) {
                /** 一次递减一个y方向增量 */
                $err -= $dy; // 调整误差
                $x0 += $sx; // 更新x坐标
            }
            /** 如果x方向增量大于误差，则需要在y方向上移动一个单位。 */
            /** 说明直线变平了，x防线增量过大，直线倾斜度变小，y方向补偿一个单位，斜率加一个x增量 */
            if ($e2 < $dx) {
                $err += $dx; // 调整误差
                $y0 += $sy; // 更新y坐标
            }
        }
    }


    /**
     * 生成渐变颜色
     * @param int $baseColor
     * @param int $fadeLevel
     * @return string
     * @note 但是cli模式下这个颜色对比实在太小了吧，
     */
    private function getFadedColor(int $baseColor, int $fadeLevel)
    {
        /** 颜色逐渐变暗 */
        return intval($baseColor) - $fadeLevel;
    }

    /**
     * 生成新的星星
     * @param int $numStars 流星总数
     * @param bool $isWaterLine 是否流线型
     * @return array
     */
    private function generateStars(int $numStars, bool $isWaterLine)
    {
        $stars = [];
        for ($i = 0; $i < $numStars; $i++) {
            $stars[] = [
                /** 随机初始角度，转换为弧度 决定流星在圆心中抛射出来的方向 */
                'angle' => mt_rand(0, 360) * M_PI / 180, //
                /** 从中心开始生成流星 若大于0则中间会留一个空腔 */
                'radius' => 0, //
                /** 调整星星速度,半径增加的速度 ，值越大，轨迹沿直径方向变化越大 */
                'speed' => $isWaterLine ? 0.1 : (0.1 + 0.1 * mt_rand(0, 5)), //
                /** 调整角速度，角度增加的速度，值越大，星星旋转的越快，绕的圆周越多 */
                'angleSpeed' => $isWaterLine ? 0.03 : (0.03 * mt_rand(1, 2)), //
                /** 随机颜色 */
                'color' => $this->getRandomColor()
            ];
        }
        return $stars;
    }

    /**
     * 计算物体的坐标
     * @param array $config
     * @param array $canvas
     * @return array
     * @note 将三维坐标投射到二维坐标
     */
    private function computeCoordinateFor3d(array $config = [], array $canvas = [])
    {
        /** 终端宽度  */
        $width = $config['width'] ?? 80;
        /** 终端高度 */
        $height = $config['height'] ?? 40;
        /** 绕x旋转角度 */
        $angleX = $config['angleX'] ?? 0;
        /** 绕y轴旋转角度 */
        $angleY = $config['angleY'] ?? 0;
        /** 绕z轴旋转角度 */
        $angleZ = $config['angleZ'] ?? 0;
        /** 图形相对于终端尺寸缩放比例 */
        $scale = $config['scale'] == 0 ? (min($width, $height) / 8) : $config['scale'];
        /** 二维平面x轴方向偏移量 */
        $distanceX = $config['distanceX'] ?? 1;
        /** 二维平面y轴方向偏移量 */
        $distanceY = $config['distanceY'] ?? 1;
        /** 三维图像构图关键点 */
        $vertices = $config['vertices'] ?? [];
        /** 三维图形绘图路径 */
        $edges = $config['edges'] ?? [];
        /** 如果没有图层，则需要创建新的图层 */
        if (empty($canvas)) {
            $canvas = array_fill(0, $height, array_fill(0, $width, ' '));
        }

        // 旋转矩阵和坐标转换（与立方体相同）
        $cosX = cos($angleX);
        $sinX = sin($angleX);
        $cosY = cos($angleY);
        $sinY = sin($angleY);
        $cosZ = cos($angleZ);
        $sinZ = sin($angleZ);

        /** 这里是将三维图像的顶点投影到二维平面 */
        $rotatedVertices = [];
        foreach ($vertices as $vertex) {
            $x = $vertex[0];
            $y = $vertex[1];
            $z = $vertex[2];

            $xz = $x * $cosZ - $y * $sinZ;
            $y = $x * $sinZ + $y * $cosZ;
            $x = $xz;

            $yz = $y * $cosX - $z * $sinX;
            $z = $y * $sinX + $z * $cosX;
            $y = $yz;

            $xz = $x * $cosY - $z * $sinY;
            $z = $x * $sinY + $z * $cosY;
            $x = $xz;

            $x *= $scale;
            $y *= $scale;
            /** 只取被位移后的x和y坐标 ，就完成了三维到二维的转换 */
            $rotatedVertices[] = [$x * 2 + $distanceX + $width / 2, $y + $distanceY + $height / 2];
        }

        /** 根据配置使用两个端点绘制三维图像的边 */
        foreach ($edges as $edge) {
            $x1 = (int)$rotatedVertices[$edge[0]][0];
            $y1 = (int)$rotatedVertices[$edge[0]][1];
            $x2 = (int)$rotatedVertices[$edge[1]][0];
            $y2 = (int)$rotatedVertices[$edge[1]][1];
            $this->drawLine($canvas, $x1, $y1, $x2, $y2);
        }

        return $canvas;
    }

    /**
     * 绘制二维动画流星
     * @param array $stars 流星坐标
     * @param array $trail 流星轨迹坐标
     * @param array $config 流星配置
     * @param array $canvas 画布
     * @return array
     */
    private function computeCoordinateFor2dCircle(array &$stars, array &$trail, array $config = [], array $canvas = [])
    {
        $maxStars = $config['maxStars'] ?? 1;
        $numStars = $config['numStars'] ?? 1;
        $isWaterLine = $config['isWaterLine'] ?? true;
        $centerX = $this->centerX;
        $centerY = $this->centerY;
        $width = $this->width;
        $height = $this->height;
        $trailLength = 6;
        /** 二维平面x轴方向偏移量 */
        $distanceX = $config['distanceX'] ?? 0;
        /** 二维平面y轴方向偏移量 */
        $distanceY = $config['distanceY'] ?? 0;

        if (empty($canvas)) {
            /** 画布 */
            $canvas = array_fill(0, $height, array_fill(0, $width, ' '));
        }

        /** 每一帧生成新的星星（只在最大星星数量内）*/
        if (count($stars) <= $maxStars) {
            $stars = array_merge($stars, $this->generateStars($numStars, $isWaterLine));
        }
        /** 更新每个星星的位置 临时存储有效的星星 */
        $newStars = [];
        foreach ($stars as &$star) {
            /** 坐标使用了三角函数计算 */
            $star['radius'] += $star['speed']; // 半径增加，模拟径向位移
            $star['angle'] += $star['angleSpeed']; // 角度增加，模拟旋转
            if ($star['angle'] >= 360) {
                $star['angle'] = $star['angle'] % 360;
            }
            /** x 坐标 = 圆心x坐标 + 半径 x 角度的余弦 需要校正x方向坐标 */
            $x = $centerX + (int)($star['radius'] * 2 * cos($star['angle']));
            /** y 坐标 = 圆心y坐标 + 半径 x 角度的正弦 */
            $y = $centerY + (int)($star['radius'] * sin($star['angle']));

            /** 在坐标系中，分成四个象限 */
            /** 确保星星位置在画布内 */
            if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
                // 更新轨迹
                for ($i = 0; $i < $trailLength; $i++) {
                    /** 尾巴总是离圆心更近一些，越是后面的尾巴，离圆心越近 */
                    /** 尾巴的x坐标 = 圆心点x的坐标 + （头部的半径 - 尾巴的长度） x 圆角的余弦 需要校正x方向坐标 */
                    $trailX = $centerX + (int)(($star['radius'] - $i * $star['speed']) * 2 * cos($star['angle'])) + $distanceX;
                    /** 尾巴的y坐标 = 圆心的y坐标 + （头部的半径 - 尾巴的长度） x 圆角的正弦 */
                    $trailY = $centerY + (int)(($star['radius'] - $i * $star['speed']) * sin($star['angle'])) + $distanceY;
                    /** 尾巴还在画布内 */
                    if ($trailX >= 0 && $trailX < $width && $trailY >= 0 && $trailY < $height) {
                        // 添加颜色到轨迹，并保证第二个星星颜色较暗
                        /** 因为流星的速度不一样，存在交叉的情况，所以会存在流星尾巴重合的情况，所以同一个坐标会有多个星星，按顺序存储星星 */
                        $trail[$trailY][$trailX][] = $this->getFadedColor($star['color'], $i);
                    }
                }
                /** 记录有效的星星 */
                $newStars[] = $star;
            }
        }
        /** 更新星星数组 */
        $stars = $newStars;
        /** 绘制轨迹：只绘制了轨迹，而并没有绘制流星本身 */
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                /** 如果这个坐标有流星的尾巴 */
                if (!empty($trail[$y][$x])) {
                    /** 这里必须按顺序获取星星的数据，否则流星颜色会混乱，这一步很关键 */
                    $lastColor = array_shift($trail[$y][$x]);
                    /** 渲染当前坐标的流星 */
                    $canvas[$y][$x] = "\033[38;5;{$lastColor}m*\033[0m";
                }
            }
        }

        return $canvas;
    }

    /**
     * 创建画布
     * @param int $height 终端高度
     * @param int $width 终端宽度
     * @return array
     */
    private function createCanvas(int $height, int $width)
    {
        /** 画布 */
        return array_fill(0, $height, array_fill(0, $width, ' '));

    }

    /**
     * 初始化
     * Client constructor.
     * @param int|float $width 终端宽度
     * @param int|float $height 终端高度
     * @param int|int $fresh 刷新速率
     */
    public function __construct(float $width = 0, float $height = 0, int $fresh = 0)
    {
        if ($width <= 0 || $height <= 0) {
            /** 获取终端的宽度和高度 */
            list($width, $height) = $this->getTerminalSize();
        }
        /** 动画刷新时间 */
        if ($fresh) {
            $this->fresh = $fresh;
        }
        if ($this->fresh <= 0) {
            $this->fresh = 1;
        }
        /** 播放窗口大小 */
        $this->width = $width;
        $this->height = $height;


        /** 圆心 */
        $this->centerX = round($width / 2); // 几何中心X
        $this->centerY = round($height / 2); // 几何中心Y


        /** 轨迹画布，存储轨迹 */
        $this->trail = array_fill(0, $height, array_fill(0, $width, []));
        /** 存储星星的数组 */
        $this->stars = [];
        /** 初始化動畫配置 */
        $this->animationsConfig = ['3d' => [], '2d' => []];
    }

    /** 被添加的所有动画配置 */
    private $animationsConfig = [];

    /**
     * 添加三维动画
     * @param array $config
     * @return string
     */
    public function add3dAnimation(array $config)
    {
        /** id */
        $id = md5(mt_rand(100, 999) . time());
        /** 初始化3d动画进行第一个动作 */
        $config['_actionIndex'] = 0;
        /** 存储每一个动画的配置 */
        $this->animationsConfig['3d'][$id] = $config;
        return $id;
    }

    /**
     * 添加流星雨背景
     * @param array $config
     * @return void
     */
    public function addStarRain(array $config)
    {
        /** id */
        $id = md5(mt_rand(100, 999) . time());
        $config['type'] = 'star_rain';
        $this->animationsConfig['2d'][$id] = $config;
    }

    /**
     * @return mixed
     * @note 每一个动画的角速度不一致，偏移量不一致
     */
    public function run()
    {
        while (true) {
            /** 清屏并移除历史记录 */
            echo "\033[H\033[J";
            /** 隐藏光标 */
            echo "\033[?25l";
            /** 创建画布 */
            $canvas = $this->createCanvas($this->height, $this->width);

            /** 渲染3d动画 */
            foreach ($this->animationsConfig['3d'] as &$config) {
                /** 三维动画的动作如果已经全部执行完毕，则从第一个动作重新开始 */
                if ($config['_actionIndex'] > (count($config['vertices']) - 1)) {
                    $config['_actionIndex'] = 0;
                }
                /** 一次执行一个动作，并更新下一次的动作 */
                $vertices = $config['vertices'][$config['_actionIndex']++];
                if (isset($config['function']) && is_callable($config['function'])) {
                    array_walk($vertices, $config['function']);
                }
                /** 立方体 */
                $config2 = [
                    'width' => $this->width,
                    'height' => $this->height,
                    'angleX' => $config['angleX'] ?? 0,
                    'angleY' => $config['angleY'] ?? 0,
                    'angleZ' => $config['angleZ'] ?? 0,
                    'scale' => $config['scale'] ?? (min($this->width, $this->height) / 8),
                    'distanceX' => $config['distanceX'] ?? 0,
                    'distanceY' => $config['distanceY'] ?? 0,
                    'vertices' => $vertices,
                    'edges' => $config['edges'],
                ];
                /** 渲染3D立方体 */
                $canvas = $this->computeCoordinateFor3d($config2, $canvas);

                /** 向右移动 */
                if ($config['directionX'] == 1) {
                    $config['distanceX']++;
                }
                /** 向左移动 */
                if ($config['directionX'] == -1) {
                    $config['distanceX']--;
                }
                /** 即将超过右边界，更换方向，向左移动 */
                if ($config['distanceX'] >= ($this->width / 2)) {
                    $config['directionX'] = -1;
                }
                /** 即将超过左边界，更换方向，向右移动 */
                if ($config['distanceX'] <= (-$this->width / 2)) {
                    $config['directionX'] = 1;
                }

                /** 向右移动 */
                if ($config['directionY'] == 1) {
                    $config['distanceY'] += 1;
                }
                /** 向左移动 */
                if ($config['directionY'] == -1) {
                    $config['distanceY'] -= 1;
                }
                /** 即将超过右边界，更换方向，向左移动 */
                if ($config['distanceY'] >= ($this->height / 2)) {
                    $config['directionY'] = -1;
                }
                /** 即将超过左边界，更换方向，向右移动 */
                if ($config['distanceY'] <= (-$this->height / 2)) {
                    $config['directionY'] = 1;
                }

                /** 实现立方体的旋转 */
                $config['angleX'] += $config['angleStepX']; // 更新X轴角度
                $config['angleY'] += $config['angleStepY']; // 更新Y轴角度
                $config['angleZ'] += $config['angleStepZ']; // 更新Z轴角度
                if ($config['angleX'] >= 2 * M_PI) $config['angleX'] -= 2 * M_PI; // 保持X轴角度在0到2π之间
                if ($config['angleY'] >= 2 * M_PI) $config['angleY'] -= 2 * M_PI; // 保持Y轴角度在0到2π之间
                if ($config['angleZ'] >= 2 * M_PI) $config['angleZ'] -= 2 * M_PI; // 保持Z轴角度在0到2π之间
            }


            /** 渲染二维动画 */
            foreach ($this->animationsConfig['2d'] as &$config) {

                if ($config['type'] == 'star_rain') {
                    /** 渲染2D流星 */
                    $config3 = [
                        'maxStars' => $config['maxStars'],
                        'numStars' => $config['numStars'],
                        'isWaterLine' => $config['isWaterLine'],
                        'centerX' => $this->centerX,
                        'centerY' => $this->centerY,
                        'width' => $this->width,
                        'height' => $this->height,
                        'trailLength' => 6,
                        'distanceX' => $config['distanceX'],
                        'distanceY' => $config['distanceY'],
                    ];
                    $canvas = $this->computeCoordinateFor2dCircle($this->stars, $this->trail, $config3, $canvas);
                    /** 向右移动 */
                    if ($config['directionX'] == 1) {
                        $config['distanceX']++;
                    }
                    /** 向左移动 */
                    if ($config['directionX'] == -1) {
                        $config['distanceX']--;
                    }
                    /** 即将超过右边界，更换方向，向左移动 */
                    if ($config['distanceX'] >= ($this->width / 2)) {
                        $config['directionX'] = -1;
                    }
                    /** 即将超过左边界，更换方向，向右移动 */
                    if ($config['distanceX'] <= (-$this->width / 2)) {
                        $config['directionX'] = 1;
                    }

                    /** 向右移动 */
                    if ($config['directionY'] == 1) {
                        $config['distanceY'] += 1;
                    }
                    /** 向左移动 */
                    if ($config['directionY'] == -1) {
                        $config['distanceY'] -= 1;
                    }
                    /** 即将超过右边界，更换方向，向左移动 */
                    if ($config['distanceY'] >= ($this->height / 2)) {
                        $config['directionY'] = -1;
                    }
                    /** 即将超过左边界，更换方向，向右移动 */
                    if ($config['distanceY'] <= (-$this->height / 2)) {
                        $config['directionY'] = 1;
                    }
                }

                if ($config['type'] == 'snow') {

                    /** 绘制背景 ，检测显示区域的每一个坐标是否有雪花，并渲染 */
                    for ($y = 0; $y < $this->height; $y++) {
                        for ($x = 0; $x < $this->width; $x++) {
                            foreach ($this->snowflakes as $snowflake) {
                                /** 当前坐标有雪花 */
                                if ($snowflake['x'] == $x && $snowflake['y'] == $y) {
                                    /** 雪花颜色 */
                                    $rgb = $snowflake['color'];
                                    /** 10% 的概率发光 */
                                    if (rand(1, 100) > 90) {
                                        $rgb = $rgb + 5;
                                        $canvas[$y][$x] = "\033[38;5;{$rgb}m※\033[0m";
                                    } else {
                                        $canvas[$y][$x] = "\033[38;5;{$rgb}m*\033[0m";
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    /** 更新雪花坐标 */
                    foreach ($this->snowflakes as &$snowflake) {
                        /** 2%的概率改变飘落方向，模拟雪花打折璇儿降落的情况，更真实 */
                        if (rand(1, 100) <= 2) {
                            $snowflake['speedX'] = -$snowflake['speedX'];
                        }
                        /** 更新雪花横向坐标 */
                        $snowflake['x'] += $snowflake['speedX'];
                        /** 更新雪花纵向坐标 */
                        $snowflake['y'] += $snowflake['speedY'];
                        /** 处理到达左右边界的情况 如果横向已经超出屏幕 ，然后更换方向 */
                        if ($snowflake['x'] < 0) {
                            $snowflake['x'] = 0;
                            /** 改变方向 */
                            $snowflake['speedX'] = -$snowflake['speedX'];
                        } elseif ($snowflake['x'] >= $this->width) {
                            $snowflake['x'] = $this->width - 1;
                            /** 改变方向 */
                            $snowflake['speedX'] = -$snowflake['speedX'];
                        }
                        /** 处理雪花到达底部的情况 */
                        if ($snowflake['y'] >= $this->height) {
                            $snowflake['y'] = 0;
                            $snowflake['x'] = rand(0, $this->width - 1);
                            /** 重新随机左右移动速度 */
                            $snowflake['speedX'] = rand(-1, 1);
                            /** 重新随机下落速度 */
                            $snowflake['speedY'] = rand(1, 1);
                        }
                    }
                }
            }

            /** 渲染页面 */
            foreach ($canvas as $line) {
                echo implode('', $line) . PHP_EOL;
            }
            /** 这个时间是看着最流畅的 */
            usleep($this->fresh * 15000);
        }
    }

    /** 雪花池 */
    private $snowflakes = [];

    /**
     * 添加雪花背景
     * @param array $config
     */
    public function addSnow(array $config)
    {
        /** id */
        $id = md5(mt_rand(100, 999) . time());
        $config['type'] = "snow";
        $this->animationsConfig['2d'][$id] = $config;
        /** 生成初始的雪花 */
        for ($i = 0; $i < $config['snowCount'] ?? 20; $i++) {
            /** 雪花数据 */
            $this->snowflakes[] = [
                'x' => rand(0, $this->width - 1),
                'y' => rand(0, $this->height - 1),
                /** 左右移动速度，-1 表示向左，1 表示向右，0 表示静止 */
                'speedX' => rand(-1, 1),
                /** 下落速度 */
                'speedY' => rand(1, 1),
                /** 颜色 随机 让颜色分布更均匀 */
                'color' => $this->getRandomColor() - rand(0, 5)
            ];
        }
    }
}