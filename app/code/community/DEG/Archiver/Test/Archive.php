<?php

/**
 * Class DEG_Archiver_Test_Archive
 * @group archiver
 */
class DEG_Archiver_Test_Archive extends EcomDev_PHPUnit_Test_Case
{
    protected $_directory;

    public function setUp()
    {
        $this->_directory = Mage::getBaseDir('export').DS.'transferred'.DS."TEST";
        $io = new Varien_Io_File();

        $io->open();

        $io->rmdirRecursive($this->_directory);
        $io->rmdirRecursive($this->_directory."_20160428101010");
        $io->rmdirRecursive($this->_directory."_20160428101010.tar");
        $io->rmdirRecursive($this->_directory."_20160428101010.tar.gz");

        $io->checkAndCreateFolder($this->_directory);

        $io->cd($this->_directory);

        $io->write('test.csv', 'test1,test2,test3');
        $io->write('test2.csv', 'test1,test2,test3');
        $io->write('test3.log', 'LOGS');

        $io->close();
    }

    public function testArchiveTarDirectory()
    {
        $arch = new TestTarArchiver();
        $arch->run();

        $this->assertFileNotExists($this->_directory.DS.'test.csv');
        $this->assertFileNotExists($this->_directory.DS.'test2.csv');
        $this->assertFileNotExists($this->_directory."20160428101010".DS.'test.csv');
        $this->assertFileNotExists($this->_directory."20160428101010".DS.'test2.csv');

        $this->assertFileNotExists($this->_directory."_20160428101010.tar");
        $this->assertFileExists($this->_directory."_20160428101010.tar.gz");
    }

    public function testArchiveTarFile()
    {
        $arch = new TestTarArchiverFile();
        $arch->run();

        $this->assertFileNotExists($this->_directory.DS.'test3.log');
        $this->assertFileNotExists($this->_directory.DS.'test3.log_2016042810101_log');

        $this->assertFileNotExists($this->_directory.DS."test3.log_2016042810101_log.tar");
        $this->assertFileExists($this->_directory.DS."test3.log_2016042810101_log.tar.gz");
    }


    public function testArchiveTarDirectoryDoesNotExist()
    {
        $io = new Varien_Io_File();
        $io->open();
        $io->rmdirRecursive($this->_directory);
        $io->close();

        $this->setExpectedException("Mage_Core_Exception", "There is no directory to archive: ".$this->_directory);
        $arch = new TestTarArchiver();
        $arch->run();
    }

    public function testArchiveTarNewDirectoryExists()
    {
        $io = new Varien_Io_File();
        $io->open();
        $io->mkdir($this->_directory."_20160428101010");
        $io->close();

        $this->setExpectedException("Mage_Core_Exception", "Archive directory already exists: ".$this->_directory."_20160428101010");
        $arch = new TestTarArchiver();
        $arch->run();
    }

    public function testArchiveTarArchiveExists()
    {
        $io = new Varien_Io_File();
        $io->open();
        $io->write($this->_directory."_20160428101010.tar", "test");
        $io->close();

        $this->setExpectedException("Mage_Core_Exception", "Archive already exists: ".$this->_directory."_20160428101010.tar");
        $arch = new TestTarArchiver();
        $arch->run();
    }

    public function testArchiveTarCompressedArchiveExists()
    {
        $io = new Varien_Io_File();
        $io->open();
        $io->write($this->_directory."_20160428101010.tar.gz", "test");
        $io->close();

        $this->setExpectedException("Mage_Core_Exception", "Compressed archive already exists: ".$this->_directory."_20160428101010.tar.gz");
        $arch = new TestTarArchiver();
        $arch->run();
    }



}

/**
 * Class TestTarArchiver
 * For testing archiving of a directory
 */
class TestTarArchiver extends DEG_Archiver_Model_Archive_Abstract
{
    public function __construct()
    {
        $this->_directoryToArchive = Mage::getBaseDir('export').DS.'transferred'.DS."TEST";
        parent::__construct();
    }

    protected function _getArchiveSuffix()
    {
        return "20160428101010";
    }
}

/**
 * Class TestTarArchiverFile
 * For testing archiving of a file
 */
class TestTarArchiverFile extends DEG_Archiver_Model_Archive_Abstract
{
    public function __construct()
    {
        $this->_directoryToArchive = Mage::getBaseDir('export').DS.'transferred'.DS."TEST".DS."test3.log";
        parent::__construct();
    }

    protected function _getArchiveSuffix()
    {
        return "2016042810101_log";
    }
}
