<?php
set_time_limit(0);

$ansiblePing = 'sudo ansible web -m command -a "true" -u root';
$pingOutputFile = '/var/www/html/ping.txt';

$output = shell_exec("{$ansiblePing} > {$pingOutputFile} 2>&1");
echo $output;

$filename = "/var/www/html/ping.txt";
$content = file_get_contents($filename);

// Initialize array to store host statuses and IP addresses
$hostsStatus = [];
$ip_array = [];

foreach (explode("\n", $content) as $line) {
    if (preg_match('/(\d+\.\d+\.\d+\.\d+) \| CHANGED/', $line, $matches)) {
        $ip = $matches[1];
        $hostsStatus[$ip] = '运行状态';
        $ip_array[] = $ip; // Add IP address to the array
    } elseif (preg_match('/(\d+\.\d+\.\d+\.\d+) \|/', $line, $matches)) {
        $ip = $matches[1];
        $hostsStatus[$ip] = '离线状态';
        $ip_array[] = $ip; // Add IP address to the array
    }
}

echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>机器状态监控</title>
    <meta http-equiv="refresh" content="10">
    <style>
        .running { color: green; font-weight: bold; }
        .offline { color: red; font-weight: bold; }
        .btn { 
            padding: 10px 20px; 
            background-color: #007bff; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
        }
        .btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h1>机器状态监控</h1>
    <table>
        <thead>
            <tr>
                <th>机器IP</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
HTML;

// Loop through each IP address in the array and generate corresponding buttons
foreach ($ip_array as $ip) {
    $status = $hostsStatus[$ip];
    $class = ($status === '运行状态') ? 'running' : 'offline';
    $buttonName = "shutdown_" . $ip;
    $button = ($status === '运行状态') ? "<td><form id='form_$ip' method='POST'><input type='hidden' name='ip_address' value='{$ip}'><input type='button' class='btn' name='{$buttonName}' value='关机' onclick='submitForm(\"form_$ip\")'></form></td>" : "<td></td>";
    echo "<tr><td>{$ip}</td><td class='{$class}'>{$status}</td>{$button}</tr>\n";

    // Execute shutdown command when button is clicked
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST[$buttonName])) {
        echo "关闭IP: {$ip}"; // Echo the IP address corresponding to the button clicked
        //$command = "sudo ansible $ip -m command -a 'shutdown -h now' -u root";
        //shell_exec($command);
    }
}

echo <<<HTML
        </tbody>
    </table>
HTML;

// Show button to shutdown all hosts if at least one host is running
$atLeastOneRunning = in_array('运行状态', $hostsStatus);
if ($atLeastOneRunning) {
    echo <<<HTML
    <form method="POST">
        <input type="hidden" name="action" value="shutdown_all">
        <input type="submit" class="btn" value="关闭所有主机">
    </form>
HTML;
}

// Execute shutdown command for all hosts when shutdown all button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'shutdown_all') {
    foreach ($ip_array as $ip) {
        if ($hostsStatus[$status] === '运行状态') {
            $command = "sudo ansible $ip -m command -a 'shutdown -h now' -u root";
            shell_exec($command);
        }
    }
}

echo <<<HTML

</body>
</html>
HTML;
?>
