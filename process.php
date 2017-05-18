<?php
//session_start();

// Include section

include_once('classes/Analysis.php');
include_once('classes/Peptide.php');
include_once('classes/Sample.php');
include_once('classes/Parser.php');
include_once('classes/QuantitativeCluster.php');
include_once('classes/Validator.php');
include_once('classes/Task.php');
include_once('classes/File.php');
include_once('classes/FileArchiveParser.php');

//------------------------------------------------------------------------------
// Functions section

function standard_deviation($sample) {
    if (is_array($sample)) {
        $mean = array_sum($sample) / count($sample);
        foreach ($sample as $key => $num) {
            $devs[$key] = pow($num - $mean, 2);
        }
        return sqrt(array_sum($devs) / (count($devs) - 1));
    }
}

function doStatistics($array, $count) {
    $output_array = array();

    foreach ($array as $key => $st) {
        if (count($st) == $count) {
            $output_array[$key]['average'] = array_sum($st) / count($st);
            $output_array[$key]['avg_mistake'] = standard_deviation($st);
            $output_array[$key]['rel_mistake'] = (standard_deviation($st) / array_sum($st) / count($st)) * 100;
        }
    }

    return $output_array;
}

//------------------------------------------------------------------------------
// Variable declaration section

$id = 0;
$average_relative_mistake = 0;

$quality = "QualityFile";
$quantity = "QuantityFile";

$psms_header = array();

$analysis = array();
$clusters = array();

$analysis_sample_assignment = array();

$standards_raw = array();
$standards_rep = array();
$standard_average_err_sample = array();
$standards_norm = array();

$unmodif_peptides = array();
$unmod_filtered = array();
$unmod_transpose = array();
$unmod_average = array();

//------------------------------------------------------------------------------
// Input filtering

$FormValidator = new ValidationSet();

$_POST['sequence'] = preg_replace('/^>.*/', "", $_POST['sequence']);

$files = $_FILES;
$post = $_POST;


$post = array_map("strip_tags", $post);
$post = array_map("trim", $post);
$post = array_map("htmlspecialchars", $post);

$texts = array('task', 'sequence', 'prot_group_acc', 'quality_identifier', 'quantity_identifier');

foreach($post as $key => $item) {
    if(preg_match('/psms/', $key)) {
        array_push($texts, $key);
        $psms_header[] = $item;
    }
}

$numbers = array('mz_diff', 'rt_diff', 'phos_threshold', 'ion_threshold');

foreach ($post as $key => $post_item) {
    if (isset($post[$key])) {
        ${$key} = $post_item;
        if (in_array($key, $texts)) {
            $FormValidator->addValidator(new TextValidator($FormValidator->returnInstance(), $key));
        } else if (in_array($key, $numbers)) {
            $FormValidator->addValidator(new NumberValidator($FormValidator->returnInstance(), $key));
        }
    }
}

$FormValidator->validate();

if ($FormValidator->hasErrors()) {
    $session = $post;

    $session['errors'] = $FormValidator->getErrors();
    $_SESSION = $session;
    header('location: index.php');
}


//------------------------------------------------------------------------------
// Parsing data, Object creation and filling

$Task = new Task($task, $prot_group_acc, $sequence, $mz_diff, $rt_diff, $ion_threshold, $phos_threshold);

$zipfile = $_FILES['input']['tmp_name'];
$ArchiveParser = new FileArchiveParser($zipfile, $quality_identifier, $quantity_identifier);
$file_containers = $ArchiveParser->createFileContainers();


foreach ($file_containers as $key1 => $container) {
    $Temp_analysis = new Analysis($container->getName(), $Task->getMzDiff(), $Task->getRtDiff());
    
    $Peptide_parser = new Parser($psms_header);
    foreach ($container->getFiles() as $key2 => $sample) {
        $Temp_sample = new Sample($id, $sample->getFilename(), get_class($sample), $Task->getIonThreshold());

        if (get_class($sample) == $quantity) {
            $analysis_sample_assignment[$container->getName()][] = $id;
            $Task->setQuantityCount($Task->getQuantityCount() + 1);
        }
        
        $handle = @fopen($sample->getPath(), "r") or die("Some files are missing.");
        $header_line = fgets($handle);
        
        if($Peptide_parser->parseHeaderLine($header_line) == FALSE) {
            fclose($handle);
            $ArchiveParser->clearFiles();
            die('PSMS header files are not correctly specified.');
        }
        
         while (!feof($handle)) {
            $peptide_line = fgets($handle);
            $line = $Peptide_parser->parseDataLine($peptide_line);
            $Temp_pep = new Peptide($line, $Task->getSequence(), $container->getName(), $id, $Task->getPhosThreshold());
            $Temp_sample->addPeptide($Temp_pep, $Task->getProtGroupAcc());
            if ($Temp_sample->getType() == $quality) {
                $Temp_analysis->addUnmodifPeptide($Temp_pep);
            }
            $Temp_sample->addStandard($Temp_pep, $Task->getStandardsAcc());
        }

        $Temp_sample->removeCorruptPeptides();
        $Temp_sample->sortPeptides();
        $Temp_sample->setCoverage($Task->getSequence());
        $Temp_sample->generatePhosphorylatedAa($Task->getPhosThreshold());
        $Temp_sample->sortPhosAa();
        $Temp_analysis->addProteinSample($Temp_sample);
        $id++;
        
        fclose($handle);
    }
    $Temp_analysis->removeCorruptPeptides();
    $analysis[] = $Temp_analysis;
}

//------------------------------------------------------------------------------
// Standards statistics

foreach ($analysis as $an) {
    foreach ($an->getProteinSamples() as $prot_sample) {
        if ($prot_sample->getType() == $quantity) {
            foreach ($prot_sample->getStandards() as $standard) {
                if (!array_key_exists($standard->getGroupAcc(), $standards_raw) ||
                        !array_key_exists($prot_sample->getId(), $standards_raw[$standard->getGroupAcc()]) ||
                        ($standards_raw[$standard->getGroupAcc()][$prot_sample->getId()] < $standard->getArea())) {
                    $standards_raw[$standard->getGroupAcc()][$prot_sample->getId()] = $standard->getArea();
                }
            }
        }
    }
}

foreach ($standards_raw as $key => &$item) {
    foreach ($item as $area_value) {
        if ($area_value <= 0) {
            unset($standards_raw[$key]);
        }
    }

    if (count($item) < $Task->getQuantityCount()) {
        unset($standards_raw[$key]);
    }
}

$standards_raw_stat = doStatistics($standards_raw, $Task->getQuantityCount());

foreach ($standards_raw as $key => $st) {
    foreach ($st as $key2 => $value) {
        $standards_rep[$key][$key2] = $value / (array_sum($st));
    }
}

foreach ($standards_rep as $sel) {
    foreach ($sel as $key => $inner_sel) {
        if (!array_key_exists($key, $standard_average_err_sample)) {
            $standard_average_err_sample[$key] = $inner_sel / count($standards_rep);
        } else {
            $standard_average_err_sample[$key] += $inner_sel / count($standards_rep);
        }
    }
}

foreach ($standards_rep as $key1 => $st) {
    foreach ($st as $key2 => $value) {
        if (isset($st[$key2])) {
            $standards_norm[$key1][$key2] = $value / $standard_average_err_sample[$key2];
        }
    }
}

$standards_norm_stat = doStatistics($standards_norm, $Task->getQuantityCount());

foreach ($standards_norm_stat as $st) {
    $average_relative_mistake += $st['rel_mistake'] / count($standards_norm_stat);
}

//------------------------------------------------------------------------------
// Unmodifiable peptides statistics

foreach ($analysis as $key => $an) {
    foreach ($an->getUnmodifPeptides() as $unmodif) {
        $unmodif_peptides[$an->getName()][$unmodif->getSequence()] = $unmodif->getArea();
    }
}

if (!empty($unmodif_peptides)) {
    $slice = 4;

    $unmodif_intersect = call_user_func_array('array_intersect_key', $unmodif_peptides);
    $unmodif_intersect = array_slice($unmodif_intersect, 0, $slice, TRUE);
    
    foreach ($unmodif_peptides as $key => $unmod) {
        $unmod_filtered[$key] = array_intersect_key($unmod, $unmodif_intersect);
    }

    foreach ($unmod_filtered as $key1 => $unmod1) {
        foreach ($unmod1 as $key2 => $unmod2) {
            $unmod_transpose[$key2][$key1] = $unmod2;
        }
    }

    foreach ($unmod_transpose as $key1 => $unmod1) {
        foreach ($unmod1 as $key2 => $unmod2) {
            $unmod_norm[$key1][$key2] = $unmod2 / array_sum($unmod1);
        }
    }

    foreach ($unmod_norm as $key1 => $unmod1) {
        foreach ($unmod1 as $key2 => $unmod2) {
            if (!array_key_exists($key2, $unmod_average)) {
                $unmod_average[$key2] = $unmod2 / count($unmod_norm);
            } else {
                $unmod_average[$key2] += $unmod2 / count($unmod_norm);
            }
        }
    }
} else {
    die("There are no non-phosphorylable peptides in these samples.");
}

//------------------------------------------------------------------------------
// Cluster generation

foreach ($analysis as $an) {
    foreach ($an->getProteinSamples() as $protein_sample) {
        if ($protein_sample->getType() == $quantity) {
            foreach ($protein_sample->getPeptides() as $peptide) {
                if ($peptide->isPhospho() && !$peptide->isStandard($an->getStandardsAcc())) {
                    $clusters_processed = 0;
                    if (empty($clusters)) {
                        $clusters[] = new QuantitativeCluster($peptide);
                    } else {
                        foreach ($clusters as $cluster) {
                            if ($cluster->compareClusterPosition($peptide)) {
                                $cluster->addPeptide($peptide);
                                $cluster->update();
                                break;
                            } else {
                                $clusters_processed++;
                            }
                        }

                        if ($clusters_processed == count($clusters)) {
                            $clusters[] = new QuantitativeCluster($peptide);
                        }
                    }
                }
            }
        }
    }
}

//------------------------------------------------------------------------------
// Cluster consolidation

$consolidated = 1;

while ($consolidated) {
    $consolidated = 0;
    foreach ($clusters as $key1 => &$cluster1) {
        foreach ($clusters as $key2 => &$cluster2) {
            if ($cluster1 !== $cluster2) {
                if ($cluster1->sameCluster($cluster2)) {
                    $consolidated++;
                    $cluster1->mergeCluster($cluster2, $Task->getRtDiff(), $Task->getMzDiff());
                    unset($clusters[$key2]);
                    unset($cluster2);
                }
            }
        }
    }
}

usort($clusters, function($a, $b) {
    return ($a->getStart() > $b->getStart()) ? 1 : -1;
});

//------------------------------------------------------------------------------
// Cluster refinement

foreach ($clusters as $cluster) {
    $cluster->sortClusterPeptides();
}

foreach ($clusters as $cluster) {
    $cluster->refineCluster($Task->getMzDiff(), $Task->getRtDiff());
}

//------------------------------------------------------------------------------
// Quantitative statistics

foreach ($clusters as $cluster) {
    $cluster->computeSamplesArea($standard_average_err_sample, $id);
}

foreach ($clusters as $cluster) {
    $cluster->computeAnalysisArea($unmod_average, $analysis_sample_assignment);
}

foreach ($clusters as $cluster) {
    $cluster->normalizeAnalysisArea();
}

//------------------------------------------------------------------------------
// Generation of report files

/*
 * Report Samples Quantity
 */

$filename_base = "results/" . date('ymd-His_') . $Task->getName();
$filename_quality = $filename_base . "_quality.txt";
$file_quality = fopen($filename_quality, "w+");

$data = "";

foreach ($analysis as $an) {
    foreach ($an->getProteinSamples() as $sample) {
        $data .= $sample;
        $data .= "\n";
        $data .= "Sequence\tPhosphorylations\tmz\tRT\tIon Score\tArea\tPhos AA Count\n";
        foreach ($sample->getPeptides() as $peptide) {
            $data .= $peptide->reportPhosphoPeptide();
        }
        $data .= "\n\n";
    }
}

fwrite($file_quality, $data);

/*
 * Report Samples Summary
 */


foreach ($analysis as $an) {
    $an->generatePhosSummary($quality);
}

$filename_summary = $filename_base . "_summary.txt";
$file_summary = fopen($filename_summary, "w+");
$header = "";

$header .= "Analysis\t";
foreach ($analysis as $an) {
    $header .= $an->getName() . "\t\t\t";
}
$header .= "\n";

$header .= "\t";
foreach ($analysis as $an) {
    $header .= "max probability\ttotal occurence";
    $header .= "\t\t";
}
$header .= "\n";

fwrite($file_summary, $header);

$summary_data = "";

foreach ($Task->getPhosphorylableAa() as $key => $phos_aa) {
    $summary_data .= $key;
    foreach ($analysis as $an) {
        $phos_summary = $an->getPhosSummary();
        if (array_key_exists($key, $phos_summary)) {
            $summary_data .= "\t" . $phos_summary[$key]['max_prob'] . "\t" . $phos_summary[$key]['tot_prob'] . "\t";
        } else {
            $summary_data .= "\t\t\t";
        }
    }
    $summary_data .= "\n";
}

fwrite($file_summary, $summary_data);



/*
 * Report Clusters
 */

$filename_clusters = $filename_base . "_clusters.txt";

$file = fopen($filename_clusters, "w+");



$cluster_data = "Cluster quantity absolute values\n";
$cluster_data .= "\t";

foreach ($analysis as $an) {
    $cluster_data .= $an->getName() . "\t";
}

$cluster_data .= "\n";


foreach ($clusters as $cluster) {
    $cluster_analysis = $cluster->getAnalysisArea();
    if ($cluster->getLength() == 1) {
        $cluster_data .= $cluster->getStart() . "\t";
    } else {
        $cluster_data .= $cluster->getStart() . " - " . $cluster->getEnd() . "\t";
    }
    foreach ($analysis as $an) {
        $cluster_data .= $cluster_analysis[$an->getName()] . "\t";
    }
    $cluster_data .= "\n";
}

$cluster_data .= "\nCluster quantity normalized values to max from cluster\n";
$cluster_data .= "\t";

foreach ($analysis as $an) {
    $cluster_data .= $an->getName() . "\t";
}

$cluster_data .= "\n";


foreach ($clusters as $cluster) {
    $cluster_analysis = $cluster->getNormalizedAnalysisArea();
    if ($cluster->getLength() == 1) {
        $cluster_data .= $cluster->getStart() . "\t";
    } else {
        $cluster_data .= $cluster->getStart() . " - " . $cluster->getEnd() . "\t";
    }
    foreach ($analysis as $an) {
        $cluster_data .= $cluster_analysis[$an->getName()] . "\t";
    }
    $cluster_data .= "\n";
}

$cluster_data .= "\n\nStandards normalized: \t";

foreach ($analysis as $an) {
    foreach ($an->getProteinSamples() as $sample) {
        if ($sample->getType() == $quantity) {
            $cluster_data .= $sample->getFilename() . "\t";
        }
    }
}
$cluster_data .= "\n";

foreach ($standards_rep as $key1 => $item) {
    $cluster_data .= $key1 . "\t";
    foreach ($item as $value) {
        $cluster_data .= $value . "\t";
    }
    $cluster_data .= "\n";
}

$cluster_data .= "Normalized average: \t";
foreach ($standard_average_err_sample as $item) {
    $cluster_data .= $item . "\t";
}
$cluster_data .= "\n";



$cluster_data .= "\n\nUnmodified peptides statistics: \t";
foreach ($analysis as $an) {
    $cluster_data .= $an->getName() . "\t";
}
$cluster_data .= "\n";

foreach ($unmod_norm as $key1 => $item) {
    $cluster_data .= $key1 . "\t";
    foreach ($item as $value) {
        $cluster_data .= $value . "\t";
    }
    $cluster_data .= "\n";
}

$cluster_data .= "Normalized average: \t";
foreach ($unmod_average as $item) {
    $cluster_data .= $item . "\t";
}
$cluster_data .= "\n";


fwrite($file, $cluster_data);

fclose($file);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <LINK REL=StyleSheet HREF="style/style.css" TYPE="text/css" MEDIA=screen>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title>Phosphorylation analysis</title>
        </head>

        <body>
            <div id="container">
                <div id="content">
                    <div class="center" id="result_header">
                        <h1>Task: "<?php echo $Task->getName(); ?>" is ready</h1>


                    </div>

                    <div class="center" id="result_body">
<?php
foreach (glob('results/*.txt') as $filename) {
    if (is_file($filename) && time() - filemtime($filename) > 60 * 60 * 24) {
        unlink($filename);
    }
}

$ArchiveParser->clearFiles();


echo "<a href=\"$filename_quality\" class=\"result\" target=\"new\">" . substr($filename_quality, 8) . "</a><br />";
echo "<a href=\"$filename_summary\" class=\"result\" target=\"new\">" . substr($filename_summary, 8) . "</a><br />";
echo "<a href=\"$filename_clusters\" class=\"result\" target=\"new\">" . substr($filename_clusters, 8) . "</a><br />";
?>
                    </div>
                </div>


            </div>
        </body>
</html>