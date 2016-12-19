<?php

namespace App\Http\Models;

use Illuminate\Filesystem\Filesystem;
use Log;

/**
 * Class Bw
 * @package App\Http\Models
 */
class Bw
{
    const COLORSPACE_RGB = 'RGB';
    const COLORSPACE_CMYK = 'CMYK';
    const COLORSPACE_GRAYSCALE = 'GRAYSCALE';
    const COLORSPACE_UNKOWN = 'UNKNOWN';
    const BW_OK  = 0.10;
    const BW_FAIL  = 0.25;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $tmpFolderPath;

    /**
     * BlackAndWhiteChecker constructor.
     * @param string $tmpFolderPath
     */
    public function __construct($tmpFolderPath='')
    {
        $this->tmpFolderPath = (empty($tmpFolderPath)===true? __DIR__."/../../../storage/temp" : $tmpFolderPath);
        $this->filesystem = new Filesystem();
    }

    /**
     * Cleanup everything from attributes to avoid memory leak.
     */
    public function __destruct(){
        foreach (get_class_vars(__CLASS__) as $clsVar => $_) {
            unset($this->$clsVar);
        }

        /** Just to make sure long process don't run out of memory. */
        gc_collect_cycles();
    }

    /**
     * @param string $url
     * @throws \Exception
     * @return mixed
     */
    public function isBlackAndWhite($url)
    {
        /** First test if asset is available. */
        $headers = get_headers($url);
        if(isset($headers[0])===false || strpos($headers[0],'200 OK')===false){
            return $this->generateResponse('Image not available from the link provided.', $url);
        }

        $tmpFilePath = $this->storeInTmpLocation($url);

        $colourSpace = $this->getColourspace($tmpFilePath);
        if ($colourSpace == self::COLORSPACE_UNKOWN) {
            $this->filesystem->delete([$tmpFilePath]);
            return $this->generateResponse("Impossible to know colour space of image '$url'", $url);
        }

        // Check that is already Grayscale
        if ($colourSpace == self::COLORSPACE_GRAYSCALE) {
            return $this->generateResponse(true, $url);
        } else {

            // Creating temp file with high Saturation
            $tmpFilePathHSL = $tmpFilePath . '-HSL';
            exec("gm convert -colorspace HSL " . $tmpFilePath . " " . $tmpFilePathHSL);
            if (file_exists($tmpFilePathHSL) !== true) {
                $this->filesystem->delete([$tmpFilePath]);
                return $this->generateResponse('Temporary HSL file does not exists. Permission problem most probable.', $url);
//                throw new FileNotFoundException(
//                    "Temporary HSL file: '$tmpFilePathHSL' does not exists. Permission problem most probable."
//                );
            }

            exec("gm identify -verbose " . $tmpFilePathHSL, $output);
            $result = $this->getBetween(implode("", $output), 'ChannelStatistics:', 'Resolution:');
            $result = $this->getBetween($result, 'Green:', 'tandardDeviation:');
            $result = $this->getBetween($result, 'Mean:', 'S');
            $result = $this->getBetween($result, '(', ')');

            // Cleaning server from files
            $this->filesystem->delete([$tmpFilePath, $tmpFilePathHSL]);

            if ($result <= self::BW_OK) {
                return $this->generateResponse(true, $url);
            } elseif ($result <= self::BW_FAIL) {
                return $this->generateResponse(false, $url);
            } else {
                return $this->generateResponse(false, $url);
            }
        }
    }

    /**
     * Translate the result of GraphicsMagick for channelDepth into colorspace
     * @param string $filePath
     * @return string
     */
    private function getColourspace($filePath)
    {
        exec(
            "gm identify -verbose " . $filePath,
            $output
        );
        $trimOutput = explode('  Channel Depths:', implode("", $output));
        if (isset($trimOutput[1])) {
            $trimOutput = explode('  Channel Statistics:', $trimOutput[1]);
            $content = $trimOutput[0];
        } else {
            return 'UNKNOWN';
        }
        $content= str_replace(" ", "", $content);
        switch ($content) {
            case "Red:8bitsGreen:8bitsBlue:8bits":
                return self::COLORSPACE_RGB;
                break;
            case "Cyan:8bitsMagenta:8bitsYellow:8bitsBlack:8bits":
                return self::COLORSPACE_CMYK;
                break;
            case "Gray:8bits":
                return self::COLORSPACE_GRAYSCALE;
                break;
            default:
                return self::COLORSPACE_UNKOWN;
        }
    }

    /**
     * @param string $url
     * @return string
     */
    private function storeInTmpLocation($url)
    {
        $destination = tempnam($this->tmpFolderPath, '');
        $this->filesystem->copy($url, $destination);
        return $destination;
    }

    /**
     * Extracts string located between other strings
     *
     * @param string $content
     * @param string $start
     * @param string $end
     *
     * @return string
     */
    private function getBetween($content, $start, $end)
    {
        $content = str_replace(" ", "", $content);
        $r = explode($start, $content);
        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return '';
    }

    /**
     * Model to return message.
     * @param $answer
     * @param $url
     * @return mixed
     */
    private function generateResponse($answer,$url)
    {
        return $answer;
    }
}