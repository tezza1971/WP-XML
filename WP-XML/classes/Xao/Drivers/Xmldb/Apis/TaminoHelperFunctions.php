<?php

function thfPrintError($taminoObject)
{
    $iResultMessageCode1 = $taminoObject->getResultMessageCode1();
    $iResultMessageCode2 = $taminoObject->getResultMessageCode2();
    $sResultMessageText1 = $taminoObject->getResultMessageText1();
    $sResultMessageText2 = $taminoObject->getResultMessageText2();
    $sResultMessageLine1 = $taminoObject->getResultMessageLine1();
    $sResultMessageLine2 = $taminoObject->getResultMessageLine2();
    $iResultHttpCode     = $taminoObject->getResultHttpCode();

    echo "<br />\n";

    if ($iResultHttpCode == 200)
    {
      echo "<b>Tamino Error ";
      if (intVal($iResultMessageCode1) > 0)
          echo $iResultMessageCode1;
      else
          echo $iResultMessageCode2;
      echo ":</b> ";
      if (strLen($sResultMessageText1) > 0)
          echo $sResultMessageText1." ";
      if (strLen($sResultMessageText2) > 0)
          echo $sResultMessageText2." ";
      if (strLen($sResultMessageLine1) > 0)
          echo "(".$sResultMessageLine1.") ";
      if (strLen($sResultMessageLine2) > 0)
          echo "(".$sResultMessageLine2.") ";
    }
    else
    {
      echo "<b>HTTP Error ".$iResultHttpCode.":</b> ";
      if ($iResultHttpCode == 401)
          echo "Authentication Required";
      elseif ($iResultHttpCode == 502)
          echo "Database not reachable";
    }
    echo "<br />\n";
    exit();
}
