<?php

/**
 * Class DEG_Archiver_Model_Archive_Abstract
 */
class DEG_Archiver_Model_Archive_Abstract
{
    /** @var string $_directoryToArchive */
    protected $_directoryToArchive;

    /** @var string $_newDirectory */
    protected $_newDirectory;

    /** @var string $_newArchive */
    protected $_newArchive;

    /** @var string $_newCompressedArchive */
    protected $_newCompressedArchive;

    /** @var string $_logFile */
    protected $_logFile;

    /** @var string $_suffixFormat */
    protected $_suffixFormat;

    /** @var Varien_Io_File $_localIo */
    protected $_localIo;

    /** @var Mage_Archive_Interface $_archiveModel */
    protected $_archiveModel;

    /**
     * DEG_Archiver_Model_Archive_Abstract constructor.
     *
     * Initialize protected variables, newDirectory and newArchive are built as directories initialized from the
     *  directoryToArchive. See methods.
     *
     */
    public function __construct()
    {
        $this->_archiveFileType = "tar";
        $this->_suffixFormat = "YmdHis";
        $this->_logFile = "archiver.log";
        $this->_localIo = new Varien_Io_File();
        $this->_archiveModel = new Mage_Archive_Tar();
        $this->_newDirectory = false;
        $this->_newArchive = false;
        $this->_newCompressedArchive = false;
    }

    /**
     * @return bool|string
     */
    protected function _getArchiveSuffix()
    {
        return date($this->_suffixFormat);
    }

    /**
     * @param string $message
     */
    protected function _log($message)
    {
        Mage::log($message, null, $this->_logFile);
    }

    /**
     * @return bool|string
     */
    protected function _getDirectoryToArchive()
    {
        if(file_exists($this->_directoryToArchive)){
            return $this->_directoryToArchive;
        }
        $this->_log("Archiver: The directory to archive: ".$this->_directoryToArchive." : does not exist, archiving will not take place");
        return false;
    }

    /**
     * Built as directoryToArchive concatenated with the archive suffix.
     *
     * @return bool|string
     */
    protected function _getNewDirectory()
    {
        $this->_newDirectory = $this->_directoryToArchive."_".$this->_getArchiveSuffix();
        if(!file_exists($this->_newDirectory)){
            return $this->_newDirectory;
        }
        $this->_log("Archiver: The new directory for archiving: ".$this->_newDirectory." : already exists, archiving will not take place");
        return false;
    }

    /**
     * Built as newDirectory concatenated with the archive type.
     *
     * @return bool|string
     */
    protected function _getNewArchive()
    {
        $this->_newArchive = $this->_newDirectory.".".$this->_archiveFileType;
        if(!file_exists($this->_newArchive)){
            return $this->_newArchive;
        }
        $this->_log("Archiver: The new archive: ".$this->_newArchive." : already exists, archiving will not take place");
        return false;
    }

    /**
     * Built as newDirectory concatenated with the archive type.
     *
     * @return bool|string
     */
    protected function _getNewCompressedArchive()
    {
        $this->_newCompressedArchive = $this->_newArchive.".gz";
        if(!file_exists($this->_newCompressedArchive)){
            return $this->_newCompressedArchive;
        }
        $this->_log("Archiver: The new compressed archive: ".$this->_newCompressedArchive." : already exists, archiving will not take place");
        return false;
    }

    /**
     * Fail the job if directories are invalid.
     * Create archive.
     * Remove newDirectory after it is no longer needed.
     *
     */
    protected function _archive()
    {
        $directoryToArchive = $this->_getDirectoryToArchive();
        if(!$directoryToArchive){
            Mage::throwException("There is no directory to archive: ".$this->_directoryToArchive);
        }
        $newDirectory = $this->_getNewDirectory();
        if(!$newDirectory){
            Mage::throwException("Archive directory already exists: ".$this->_newDirectory);
        }
        $archive = $this->_getNewArchive();
        if(!$archive){
            Mage::throwException("Archive already exists: ".$this->_newArchive);
        }
        $compressedArchive = $this->_getNewCompressedArchive();
        if(!$compressedArchive){
            Mage::throwException("Compressed archive already exists: ".$this->_newCompressedArchive);
        }

        $this->_localIo->open();
        $this->_localIo->mv($directoryToArchive, $newDirectory);
        $this->_log("The directory ".$directoryToArchive." has been moved to ".$newDirectory." for archiving");

        $archive = $this->_pack($newDirectory, $archive);
        if(file_exists($archive)){
            $this->_log("The directory ".$newDirectory." has been archived into ".$archive);
            $this->_localIo->rmdir($newDirectory, true);
            $this->_log("The directory ".$newDirectory." has been removed");
            $compressedArchive = $this->_compress($archive, $compressedArchive);
            if(file_exists($compressedArchive)){
                $this->_log("The archive ".$archive." has been compressed into ".$compressedArchive);
                $this->_localIo->rm($archive);
            }
        }
        $this->_localIo->close();
    }

    /**
     * @param string $newDirectory
     * @param string $archive
     *
     * @return string
     */
    protected function _pack($newDirectory, $archive)
    {
        return $this->_archiveModel->pack($newDirectory, $archive);
    }

    /**
     * Archive files that have already been uploaded ftp server
     */
    public function run()
    {
        $this->_log("Beginning to archive ".$this->_directoryToArchive);
        $this->_archive();
        $this->_log("Finished to archiving ".$this->_directoryToArchive);
    }

    protected function _compress($archive, $compressedArchive)
    {
        $gz = new Mage_Archive_Gz();
        return $gz->pack($archive, $compressedArchive);
    }
}
