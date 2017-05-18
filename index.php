<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <LINK REL=StyleSheet HREF="style/style.css" TYPE="text/css" MEDIA=screen>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title>Phosphorylation analysis</title>
        </head>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js" ></script>

        </script>

        <script>
            $(document).ready(function() {

                $('#show_options').on('click', function() {
                    $('#options').toggle();

                    if ($('#options').css("display") === "block") {
                        $('#show_options').attr("value", "Hide Options");
                    } else {
                        $('#show_options').attr("value", "Show Options");
                    }
                });

                $('#show_guidelines').on('click', function() {
                    $('#guidelines').toggle();

                    if ($('#guidelines').css("display") === "block") {
                        $('#show_guidelines').attr("value", "Hide Guidelines");
                    } else {
                        $('#show_guidelines').attr("value", "Show Guidelines");
                    }
                });


                $("#go").click(function() {
                    $("#form_data").submit();
                });
            });
        </script>

        <?php
        session_start();
        
        function formValue($field, $else) {
            if (isset($_SESSION[$field])) {
                echo $_SESSION[$field];
            } else {
                echo $else;
            }
        }
        
        function returnFormValue($field, $else) {
            if (isset($_SESSION[$field])) {
                return $_SESSION[$field];
            } else {
                return $else;
            }
        }

        function formError($field) {
            if (isset($_SESSION['errors'][$field])) {
                echo "<span class=\"warning\">" . $_SESSION['errors'][$field] . "</span>";
            }
        }
        
        function input_field($label, $name, $default) {
            $value = returnFormValue($name, $default);
            echo "<label>$label</label>";
            echo "<input type=\"text\" name=\"$name\" value=\"$value\" size=\"20\" class=\"input_box\" /><br />";
            formError("quality_identifier");
        }
        
        ?>

        <body>
            <div id="container">
                
                <div id="content">
                    <h1>Orbitrap Script 2.0</h1>
                    <h2>Analysis of MS phosphorylation</h2>
                    <hr class="left"/><input type="button" id="show_guidelines" value="Show Guidelines" class="show"/>
                    <div id="guidelines">
                        <ul>
                            <li>Script accepts psms text data files in tab separated format</li>
                            <li>Script accepts single zip file created according to following rules:
                                <ul>
                                <li>Zip file includes directories with sample names</li>
                                <li>Every directory has single file for qualitative analysis and number of files for quantitative analysis</li>
                                <li>Name of files for qualitative analysis and for quantitative analysis have to be distinguishable by unique identifiers, which can be specified below</li>
                                </ul>
                            </li>
                            <li>At least 2 distinct files have to be supplied</li>
                            <li>For overview of accepted format see example files from <a href="sample_files.zip">sample_files.zip</a> from human <a href="http://www.uniprot.org/uniprot/O14641.fasta">Dishevelled 2</a></li>
                            <li>All parameters have to be specified</li>
                            <li>Script produces 3 files.
                                <ul>
                                   <li>Report for qualitative analysis with sample characteristics and peptide data</li>
                                   <li>Summary of phosphorylations from every sample</li>
                                   <li>Quantitative analysis of individual cluster phosphorylations</li>
                                </ul>
                            </li>
                                <li>Result files are kept on server for 24 hours</li>
                        </ul>
                    </div>
                    <br /><br />
                    
                    <form action="process.php" id="form_data" method="post" enctype="multipart/form-data">

                        <input type="hidden" id="count" name="count" value="2" />
                        
                        <?php input_field("Task name: ", "task", ""); ?>

                        <label for="sequence">Protein sequence: </label>
                        <textarea name="sequence" class="input_box"><?php formValue("sequence", "") ?></textarea><br />
                        <?php formError("sequence") ?>

                        <?php input_field("Protein Group Accession: ", "prot_group_acc", "234 gi"); ?>

                        <div id="file">
                            <label>Input:</label><input type="file" name="input" />            
                        </div>
                        
                        <?php input_field("Quality File Identifier:", "quality_identifier", "ID"); ?>
                        
                        <label>Quantity File Identifier: </label>
                        <input type="text" name="quantity_identifier" value="<?php formValue("quantity_identifier", "Phos") ?>" size=20" class="input_box" /><br />
                        <?php formError("quality_identifier") ?>
                        <br /><br />
                        
                        
                        <div class="center">
                            <input type="button" id="go" name="go" value="Submit" class="button" />
                        </div>


                        <hr class="left"/><input type="button" id="show_options" value="Show Options" class="show"/>
                        <div id="options">
                            
                            <h2>Parameters</h2>
                            <?php                             
                            input_field("Ion Score cutoff: ", "ion_threshold", "36");
                            input_field("Phosphorylation probability cutoff (in %): ", "phos_threshold", "10");
                            input_field("M/Z Difference: ", "mz_diff", "0.5");
                            input_field("Retention Time Difference: ", "rt_diff", "2");  
                            ?>            
                            <br /><br />
                            
                            <h2>PSMS file headers</h2>

                            <?php                             
                            input_field("Sequence: ", "sequence_psms_header", "Sequence");
                            input_field("Protein Group Accessions: ", "prot_group_acc_psms_header", "Protein Group Accessions");
                            input_field("Phospho RS Site Probabilities: ", "phospho_rs_psms_header", "pRS Site Probabilities");
                            input_field("Precursor Area: ", "area_psms_header", "Precursor Area");
                            input_field("IonScore: ", "ion_score_psms_header", "IonScore"); 
                            input_field("M/Z: ", "mz_psms_header", "m/z [Da]"); 
                            input_field("Retention Time: ", "rt_psms_header", "RT [min]"); 
                            input_field("First Scan: ", "first_scan_psms_header", "First Scan"); 
                            input_field("Spectrum File: ", "spectrum_file_psms_header", "Spectrum File"); 
                            ?> 

                        </div>
                    </form> 
                </div>
                
            </div>

        </body>
</html>

<?php session_unset(); ?>