<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <LINK REL=StyleSheet HREF="style/style.css" TYPE="text/css" MEDIA=screen />
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Phosphorylation results</title>
    </head>

    <body>
        <div id="container">
            <div id="content">
                
                <h1>Files currently stored on server: </h1>
                <?php
                /*$path = opendir('results/');
                $count = 0;
                $dir_array = array();

                while (($entry = readdir($path)) != FALSE) {
                    if ($entry != "." && $entry != "..") {
                        $dir_array[] = $entry;
                    }
                }
                
                sort($dir_array);
                
                foreach($dir_array as $item) {
                    echo "<p><a href=\"results/" . $item . "\">" . $item . "</a></p>";
                    $count++;
                    if ($count % 3 == 0) {
                        echo "<br />";
                    }
                }
                closedir($path);*/
                
                $iterator = new RecursiveDirectoryIterator('results/', RecursiveDirectoryIterator::SKIP_DOTS);
                
                $count = 0;
                foreach($iterator as $filename) {
                    $dir_array[$filename->getBasename()]['size'] = $filename->getSize();
                    $dir_array[$filename->getBasename()]['time'] = $filename->getCTime();
                    /*echo $filename->getBasename()."<br />";
                    $count++;
                    if ($count % 3 == 0) {
                        echo "<br />";
                    }*/
                }
                
                ksort($dir_array);
                
                echo "<table cellspacing=\"0\" border>";
                echo "<tr>";
                echo "<th>filename</th><th>size</th><th>date</th><th>time</th>";
                echo "</tr>";
                foreach($dir_array as $key => $item) {
                    echo "<tr>";
                    echo "<td><a href=\"results/$key\">".$key."</a></td><td>".$item['size']."</td><td>".gmdate('d.m.Y' , $item['time'])."</td><td>".gmdate('H:m:s' , $item['time'])."</td>";
                    echo "</tr>";
                    $count++;
                    if ($count % 3 == 0) {
                        echo "<tr><td colspan=\"4\">&nbsp</td></tr>";
                    }
                }
                echo "</table>";
                
                ?>
            </div>
        </div>
    </body>
</html>