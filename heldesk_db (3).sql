-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2026 at 09:15 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `heldesk_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:3;', 1790435828),
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1790435828;', 1790435828);
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-roster:project:v3:866b5da02baf547445ed5994b24abaf8', 'O:26:\"Laravel\\Roster\\ProjectScan\":8:{s:8:\"basePath\";s:45:\"C:\\project\\project-helpdesk\\helpdesk-backend\\\";s:3:\"php\";O:35:\"Laravel\\Roster\\Ecosystems\\Ecosystem\":2:{s:9:\"\0*\0byName\";a:132:{s:23:\"barryvdh/laravel-dompdf\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"barryvdh/laravel-dompdf\";s:10:\"\0*\0version\";s:5:\"3.1.2\";s:9:\"\0*\0source\";E:43:\"Laravel\\Roster\\Enums\\PackageSource:Composer\";s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^3.1\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\barryvdh\\laravel-dompdf\";}s:10:\"brick/math\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"brick/math\";s:10:\"\0*\0version\";s:6:\"0.18.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:62:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\brick\\math\";}s:31:\"carbonphp/carbon-doctrine-types\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"carbonphp/carbon-doctrine-types\";s:10:\"\0*\0version\";s:5:\"3.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:83:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\carbonphp\\carbon-doctrine-types\";}s:13:\"composer/pcre\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"composer/pcre\";s:10:\"\0*\0version\";s:5:\"3.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:65:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\composer\\pcre\";}s:15:\"composer/semver\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"composer/semver\";s:10:\"\0*\0version\";s:5:\"3.4.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\composer\\semver\";}s:23:\"dflydev/dot-access-data\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"dflydev/dot-access-data\";s:10:\"\0*\0version\";s:5:\"3.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\dflydev\\dot-access-data\";}s:18:\"doctrine/inflector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"doctrine/inflector\";s:10:\"\0*\0version\";s:5:\"2.1.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:70:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\doctrine\\inflector\";}s:14:\"doctrine/lexer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"doctrine/lexer\";s:10:\"\0*\0version\";s:5:\"3.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\doctrine\\lexer\";}s:13:\"dompdf/dompdf\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"dompdf/dompdf\";s:10:\"\0*\0version\";s:5:\"3.1.6\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:65:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\dompdf\\dompdf\";}s:19:\"dompdf/php-font-lib\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"dompdf/php-font-lib\";s:10:\"\0*\0version\";s:5:\"1.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\dompdf\\php-font-lib\";}s:18:\"dompdf/php-svg-lib\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"dompdf/php-svg-lib\";s:10:\"\0*\0version\";s:5:\"1.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:70:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\dompdf\\php-svg-lib\";}s:29:\"dragonmantank/cron-expression\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"dragonmantank/cron-expression\";s:10:\"\0*\0version\";s:5:\"3.6.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:81:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\dragonmantank\\cron-expression\";}s:23:\"egulias/email-validator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"egulias/email-validator\";s:10:\"\0*\0version\";s:5:\"4.0.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\egulias\\email-validator\";}s:19:\"ezyang/htmlpurifier\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"ezyang/htmlpurifier\";s:10:\"\0*\0version\";s:6:\"4.19.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\ezyang\\htmlpurifier\";}s:18:\"fruitcake/php-cors\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"fruitcake/php-cors\";s:10:\"\0*\0version\";s:5:\"1.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:70:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\fruitcake\\php-cors\";}s:27:\"graham-campbell/result-type\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"graham-campbell/result-type\";s:10:\"\0*\0version\";s:5:\"1.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:79:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\graham-campbell\\result-type\";}s:17:\"guzzlehttp/guzzle\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"guzzlehttp/guzzle\";s:10:\"\0*\0version\";s:5:\"8.1.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\guzzlehttp\\guzzle\";}s:19:\"guzzlehttp/promises\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"guzzlehttp/promises\";s:10:\"\0*\0version\";s:5:\"3.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\guzzlehttp\\promises\";}s:15:\"guzzlehttp/psr7\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"guzzlehttp/psr7\";s:10:\"\0*\0version\";s:5:\"3.1.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\guzzlehttp\\psr7\";}s:23:\"guzzlehttp/uri-template\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"guzzlehttp/uri-template\";s:10:\"\0*\0version\";s:5:\"2.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\guzzlehttp\\uri-template\";}s:17:\"laravel/framework\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"laravel/framework\";s:10:\"\0*\0version\";s:7:\"13.30.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^13.17\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\framework\";}s:15:\"laravel/prompts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"laravel/prompts\";s:10:\"\0*\0version\";s:6:\"0.3.24\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\prompts\";}s:15:\"laravel/sanctum\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"laravel/sanctum\";s:10:\"\0*\0version\";s:5:\"4.3.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^4.0\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\sanctum\";}s:28:\"laravel/serializable-closure\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"laravel/serializable-closure\";s:10:\"\0*\0version\";s:6:\"2.0.16\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:80:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\serializable-closure\";}s:14:\"laravel/tinker\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"laravel/tinker\";s:10:\"\0*\0version\";s:5:\"3.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^3.0\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\tinker\";}s:17:\"league/commonmark\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"league/commonmark\";s:10:\"\0*\0version\";s:6:\"2.10.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\league\\commonmark\";}s:13:\"league/config\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"league/config\";s:10:\"\0*\0version\";s:5:\"1.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:65:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\league\\config\";}s:16:\"league/flysystem\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"league/flysystem\";s:10:\"\0*\0version\";s:6:\"3.36.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\league\\flysystem\";}s:22:\"league/flysystem-local\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"league/flysystem-local\";s:10:\"\0*\0version\";s:6:\"3.35.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\league\\flysystem-local\";}s:26:\"league/mime-type-detection\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"league/mime-type-detection\";s:10:\"\0*\0version\";s:6:\"1.17.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:78:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\league\\mime-type-detection\";}s:10:\"league/uri\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"league/uri\";s:10:\"\0*\0version\";s:5:\"7.8.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:62:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\league\\uri\";}s:21:\"league/uri-interfaces\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"league/uri-interfaces\";s:10:\"\0*\0version\";s:5:\"7.8.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:73:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\league\\uri-interfaces\";}s:17:\"maatwebsite/excel\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"maatwebsite/excel\";s:10:\"\0*\0version\";s:6:\"3.1.70\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"3.1.70\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\maatwebsite\\excel\";}s:23:\"maennchen/zipstream-php\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"maennchen/zipstream-php\";s:10:\"\0*\0version\";s:5:\"3.2.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\maennchen\\zipstream-php\";}s:17:\"markbaker/complex\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"markbaker/complex\";s:10:\"\0*\0version\";s:5:\"3.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\markbaker\\complex\";}s:16:\"markbaker/matrix\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"markbaker/matrix\";s:10:\"\0*\0version\";s:5:\"3.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\markbaker\\matrix\";}s:17:\"masterminds/html5\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"masterminds/html5\";s:10:\"\0*\0version\";s:6:\"2.11.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\masterminds\\html5\";}s:15:\"monolog/monolog\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"monolog/monolog\";s:10:\"\0*\0version\";s:6:\"3.11.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\monolog\\monolog\";}s:13:\"nesbot/carbon\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"nesbot/carbon\";s:10:\"\0*\0version\";s:6:\"3.13.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:65:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\nesbot\\carbon\";}s:12:\"nette/schema\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"nette/schema\";s:10:\"\0*\0version\";s:5:\"1.3.6\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:64:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\nette\\schema\";}s:11:\"nette/utils\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"nette/utils\";s:10:\"\0*\0version\";s:5:\"4.1.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:63:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\nette\\utils\";}s:16:\"nikic/php-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"nikic/php-parser\";s:10:\"\0*\0version\";s:5:\"5.8.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\nikic\\php-parser\";}s:19:\"nunomaduro/termwind\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"nunomaduro/termwind\";s:10:\"\0*\0version\";s:5:\"2.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\nunomaduro\\termwind\";}s:24:\"phpoffice/phpspreadsheet\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:24:\"phpoffice/phpspreadsheet\";s:10:\"\0*\0version\";s:6:\"1.30.7\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:76:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpoffice\\phpspreadsheet\";}s:19:\"phpoption/phpoption\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"phpoption/phpoption\";s:10:\"\0*\0version\";s:6:\"1.10.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpoption\\phpoption\";}s:9:\"psr/clock\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"psr/clock\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:61:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\clock\";}s:13:\"psr/container\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"psr/container\";s:10:\"\0*\0version\";s:5:\"2.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:65:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\container\";}s:20:\"psr/event-dispatcher\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"psr/event-dispatcher\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:72:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\event-dispatcher\";}s:15:\"psr/http-client\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"psr/http-client\";s:10:\"\0*\0version\";s:5:\"1.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\http-client\";}s:16:\"psr/http-factory\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"psr/http-factory\";s:10:\"\0*\0version\";s:5:\"1.1.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\http-factory\";}s:16:\"psr/http-message\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"psr/http-message\";s:10:\"\0*\0version\";s:3:\"2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\http-message\";}s:7:\"psr/log\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"psr/log\";s:10:\"\0*\0version\";s:5:\"3.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:59:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\log\";}s:16:\"psr/simple-cache\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"psr/simple-cache\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psr\\simple-cache\";}s:9:\"psy/psysh\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"psy/psysh\";s:10:\"\0*\0version\";s:7:\"0.12.24\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:61:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\psy\\psysh\";}s:17:\"ramsey/collection\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"ramsey/collection\";s:10:\"\0*\0version\";s:5:\"2.1.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\ramsey\\collection\";}s:11:\"ramsey/uuid\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"ramsey/uuid\";s:10:\"\0*\0version\";s:5:\"4.9.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:63:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\ramsey\\uuid\";}s:25:\"sabberworm/php-css-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"sabberworm/php-css-parser\";s:10:\"\0*\0version\";s:5:\"9.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sabberworm\\php-css-parser\";}s:28:\"spatie/laravel-package-tools\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"spatie/laravel-package-tools\";s:10:\"\0*\0version\";s:6:\"1.93.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:80:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\spatie\\laravel-package-tools\";}s:25:\"spatie/laravel-permission\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"spatie/laravel-permission\";s:10:\"\0*\0version\";s:5:\"8.3.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^8.3\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\spatie\\laravel-permission\";}s:13:\"symfony/clock\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"symfony/clock\";s:10:\"\0*\0version\";s:5:\"7.4.8\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:65:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\clock\";}s:15:\"symfony/console\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"symfony/console\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\console\";}s:20:\"symfony/css-selector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"symfony/css-selector\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:72:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\css-selector\";}s:29:\"symfony/deprecation-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"symfony/deprecation-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:81:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\deprecation-contracts\";}s:21:\"symfony/error-handler\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"symfony/error-handler\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:73:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\error-handler\";}s:24:\"symfony/event-dispatcher\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:24:\"symfony/event-dispatcher\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:76:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\event-dispatcher\";}s:34:\"symfony/event-dispatcher-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:34:\"symfony/event-dispatcher-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:86:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\event-dispatcher-contracts\";}s:14:\"symfony/finder\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"symfony/finder\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\finder\";}s:23:\"symfony/http-foundation\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"symfony/http-foundation\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\http-foundation\";}s:19:\"symfony/http-kernel\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"symfony/http-kernel\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\http-kernel\";}s:14:\"symfony/mailer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"symfony/mailer\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\mailer\";}s:12:\"symfony/mime\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"symfony/mime\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:64:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\mime\";}s:22:\"symfony/polyfill-ctype\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-ctype\";s:10:\"\0*\0version\";s:6:\"1.37.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-ctype\";}s:30:\"symfony/polyfill-intl-grapheme\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:30:\"symfony/polyfill-intl-grapheme\";s:10:\"\0*\0version\";s:6:\"1.41.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:82:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-intl-grapheme\";}s:25:\"symfony/polyfill-intl-idn\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"symfony/polyfill-intl-idn\";s:10:\"\0*\0version\";s:6:\"1.42.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-intl-idn\";}s:32:\"symfony/polyfill-intl-normalizer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:32:\"symfony/polyfill-intl-normalizer\";s:10:\"\0*\0version\";s:6:\"1.42.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:84:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-intl-normalizer\";}s:25:\"symfony/polyfill-mbstring\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"symfony/polyfill-mbstring\";s:10:\"\0*\0version\";s:6:\"1.38.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-mbstring\";}s:22:\"symfony/polyfill-php80\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php80\";s:10:\"\0*\0version\";s:6:\"1.37.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-php80\";}s:22:\"symfony/polyfill-php82\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php82\";s:10:\"\0*\0version\";s:6:\"1.38.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-php82\";}s:22:\"symfony/polyfill-php83\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php83\";s:10:\"\0*\0version\";s:6:\"1.41.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-php83\";}s:22:\"symfony/polyfill-php84\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php84\";s:10:\"\0*\0version\";s:6:\"1.38.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-php84\";}s:22:\"symfony/polyfill-php85\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php85\";s:10:\"\0*\0version\";s:6:\"1.41.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-php85\";}s:22:\"symfony/polyfill-php86\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php86\";s:10:\"\0*\0version\";s:6:\"1.41.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-php86\";}s:21:\"symfony/polyfill-uuid\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"symfony/polyfill-uuid\";s:10:\"\0*\0version\";s:6:\"1.37.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:73:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\polyfill-uuid\";}s:15:\"symfony/process\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"symfony/process\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\process\";}s:15:\"symfony/routing\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"symfony/routing\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\routing\";}s:25:\"symfony/service-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"symfony/service-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\service-contracts\";}s:14:\"symfony/string\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"symfony/string\";s:10:\"\0*\0version\";s:6:\"7.4.15\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\string\";}s:19:\"symfony/translation\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"symfony/translation\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\translation\";}s:29:\"symfony/translation-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"symfony/translation-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:81:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\translation-contracts\";}s:11:\"symfony/uid\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"symfony/uid\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:63:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\uid\";}s:18:\"symfony/var-dumper\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"symfony/var-dumper\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:70:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\var-dumper\";}s:21:\"thecodingmachine/safe\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"thecodingmachine/safe\";s:10:\"\0*\0version\";s:5:\"3.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:73:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\thecodingmachine\\safe\";}s:33:\"tijsverkoyen/css-to-inline-styles\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:33:\"tijsverkoyen/css-to-inline-styles\";s:10:\"\0*\0version\";s:5:\"2.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:85:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\tijsverkoyen\\css-to-inline-styles\";}s:16:\"vlucas/phpdotenv\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"vlucas/phpdotenv\";s:10:\"\0*\0version\";s:5:\"5.7.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\vlucas\\phpdotenv\";}s:19:\"voku/portable-ascii\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"voku/portable-ascii\";s:10:\"\0*\0version\";s:5:\"2.1.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\voku\\portable-ascii\";}s:14:\"fakerphp/faker\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"fakerphp/faker\";s:10:\"\0*\0version\";s:6:\"1.24.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:5:\"^1.23\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\fakerphp\\faker\";}s:11:\"filp/whoops\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"filp/whoops\";s:10:\"\0*\0version\";s:6:\"2.18.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:63:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\filp\\whoops\";}s:21:\"hamcrest/hamcrest-php\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"hamcrest/hamcrest-php\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:73:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\hamcrest\\hamcrest-php\";}s:22:\"laravel/agent-detector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"laravel/agent-detector\";s:10:\"\0*\0version\";s:5:\"2.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\agent-detector\";}s:13:\"laravel/boost\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"laravel/boost\";s:10:\"\0*\0version\";s:5:\"2.9.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^2.9\";s:7:\"\0*\0path\";s:65:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\boost\";}s:11:\"laravel/mcp\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"laravel/mcp\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:63:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\mcp\";}s:12:\"laravel/pail\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"laravel/pail\";s:10:\"\0*\0version\";s:5:\"1.2.7\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^1.2.5\";s:7:\"\0*\0path\";s:64:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\pail\";}s:11:\"laravel/pao\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"laravel/pao\";s:10:\"\0*\0version\";s:5:\"1.1.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^1.0.6\";s:7:\"\0*\0path\";s:63:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\pao\";}s:12:\"laravel/pint\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"laravel/pint\";s:10:\"\0*\0version\";s:6:\"1.30.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:5:\"^1.27\";s:7:\"\0*\0path\";s:64:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\pint\";}s:14:\"laravel/roster\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"laravel/roster\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\laravel\\roster\";}s:15:\"mockery/mockery\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"mockery/mockery\";s:10:\"\0*\0version\";s:6:\"1.6.15\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^1.6\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\mockery\\mockery\";}s:17:\"myclabs/deep-copy\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"myclabs/deep-copy\";s:10:\"\0*\0version\";s:6:\"1.14.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\myclabs\\deep-copy\";}s:20:\"nunomaduro/collision\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"nunomaduro/collision\";s:10:\"\0*\0version\";s:5:\"8.9.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^8.6\";s:7:\"\0*\0path\";s:72:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\nunomaduro\\collision\";}s:16:\"phar-io/manifest\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"phar-io/manifest\";s:10:\"\0*\0version\";s:5:\"2.0.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:68:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phar-io\\manifest\";}s:15:\"phar-io/version\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"phar-io/version\";s:10:\"\0*\0version\";s:5:\"3.2.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phar-io\\version\";}s:25:\"phpunit/php-code-coverage\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"phpunit/php-code-coverage\";s:10:\"\0*\0version\";s:6:\"12.5.7\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpunit\\php-code-coverage\";}s:25:\"phpunit/php-file-iterator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"phpunit/php-file-iterator\";s:10:\"\0*\0version\";s:5:\"6.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpunit\\php-file-iterator\";}s:19:\"phpunit/php-invoker\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"phpunit/php-invoker\";s:10:\"\0*\0version\";s:5:\"6.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:71:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpunit\\php-invoker\";}s:25:\"phpunit/php-text-template\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"phpunit/php-text-template\";s:10:\"\0*\0version\";s:5:\"5.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpunit\\php-text-template\";}s:17:\"phpunit/php-timer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"phpunit/php-timer\";s:10:\"\0*\0version\";s:5:\"8.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpunit\\php-timer\";}s:15:\"phpunit/phpunit\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"phpunit/phpunit\";s:10:\"\0*\0version\";s:7:\"12.5.34\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:8:\"^12.5.12\";s:7:\"\0*\0path\";s:67:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\phpunit\\phpunit\";}s:20:\"sebastian/cli-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"sebastian/cli-parser\";s:10:\"\0*\0version\";s:5:\"4.2.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:72:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\cli-parser\";}s:20:\"sebastian/comparator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"sebastian/comparator\";s:10:\"\0*\0version\";s:5:\"7.1.8\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:72:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\comparator\";}s:20:\"sebastian/complexity\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"sebastian/complexity\";s:10:\"\0*\0version\";s:5:\"5.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:72:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\complexity\";}s:14:\"sebastian/diff\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"sebastian/diff\";s:10:\"\0*\0version\";s:5:\"7.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\diff\";}s:21:\"sebastian/environment\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"sebastian/environment\";s:10:\"\0*\0version\";s:5:\"8.1.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:73:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\environment\";}s:18:\"sebastian/exporter\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"sebastian/exporter\";s:10:\"\0*\0version\";s:5:\"7.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:70:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\exporter\";}s:22:\"sebastian/global-state\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"sebastian/global-state\";s:10:\"\0*\0version\";s:5:\"8.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:74:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\global-state\";}s:23:\"sebastian/lines-of-code\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"sebastian/lines-of-code\";s:10:\"\0*\0version\";s:5:\"4.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\lines-of-code\";}s:27:\"sebastian/object-enumerator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"sebastian/object-enumerator\";s:10:\"\0*\0version\";s:5:\"7.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:79:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\object-enumerator\";}s:26:\"sebastian/object-reflector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"sebastian/object-reflector\";s:10:\"\0*\0version\";s:5:\"5.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:78:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\object-reflector\";}s:27:\"sebastian/recursion-context\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"sebastian/recursion-context\";s:10:\"\0*\0version\";s:5:\"7.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:79:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\recursion-context\";}s:14:\"sebastian/type\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"sebastian/type\";s:10:\"\0*\0version\";s:5:\"6.0.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:66:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\type\";}s:17:\"sebastian/version\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"sebastian/version\";s:10:\"\0*\0version\";s:5:\"6.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\sebastian\\version\";}s:28:\"staabm/side-effects-detector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"staabm/side-effects-detector\";s:10:\"\0*\0version\";s:5:\"1.0.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:80:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\staabm\\side-effects-detector\";}s:12:\"symfony/yaml\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"symfony/yaml\";s:10:\"\0*\0version\";s:6:\"7.4.18\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:64:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\symfony\\yaml\";}s:17:\"theseer/tokenizer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"theseer/tokenizer\";s:10:\"\0*\0version\";s:5:\"2.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\vendor\\theseer\\tokenizer\";}}s:11:\"\0*\0packages\";O:32:\"Laravel\\Roster\\PackageCollection\":2:{s:8:\"\0*\0items\";a:132:{i:0;r:5;i:1;r:13;i:2;r:21;i:3;r:29;i:4;r:37;i:5;r:45;i:6;r:53;i:7;r:61;i:8;r:69;i:9;r:77;i:10;r:85;i:11;r:93;i:12;r:101;i:13;r:109;i:14;r:117;i:15;r:125;i:16;r:133;i:17;r:141;i:18;r:149;i:19;r:157;i:20;r:165;i:21;r:173;i:22;r:181;i:23;r:189;i:24;r:197;i:25;r:205;i:26;r:213;i:27;r:221;i:28;r:229;i:29;r:237;i:30;r:245;i:31;r:253;i:32;r:261;i:33;r:269;i:34;r:277;i:35;r:285;i:36;r:293;i:37;r:301;i:38;r:309;i:39;r:317;i:40;r:325;i:41;r:333;i:42;r:341;i:43;r:349;i:44;r:357;i:45;r:365;i:46;r:373;i:47;r:381;i:48;r:389;i:49;r:397;i:50;r:405;i:51;r:413;i:52;r:421;i:53;r:429;i:54;r:437;i:55;r:445;i:56;r:453;i:57;r:461;i:58;r:469;i:59;r:477;i:60;r:485;i:61;r:493;i:62;r:501;i:63;r:509;i:64;r:517;i:65;r:525;i:66;r:533;i:67;r:541;i:68;r:549;i:69;r:557;i:70;r:565;i:71;r:573;i:72;r:581;i:73;r:589;i:74;r:597;i:75;r:605;i:76;r:613;i:77;r:621;i:78;r:629;i:79;r:637;i:80;r:645;i:81;r:653;i:82;r:661;i:83;r:669;i:84;r:677;i:85;r:685;i:86;r:693;i:87;r:701;i:88;r:709;i:89;r:717;i:90;r:725;i:91;r:733;i:92;r:741;i:93;r:749;i:94;r:757;i:95;r:765;i:96;r:773;i:97;r:781;i:98;r:789;i:99;r:797;i:100;r:805;i:101;r:813;i:102;r:821;i:103;r:829;i:104;r:837;i:105;r:845;i:106;r:853;i:107;r:861;i:108;r:869;i:109;r:877;i:110;r:885;i:111;r:893;i:112;r:901;i:113;r:909;i:114;r:917;i:115;r:925;i:116;r:933;i:117;r:941;i:118;r:949;i:119;r:957;i:120;r:965;i:121;r:973;i:122;r:981;i:123;r:989;i:124;r:997;i:125;r:1005;i:126;r:1013;i:127;r:1021;i:128;r:1029;i:129;r:1037;i:130;r:1045;i:131;r:1053;}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}}s:2:\"js\";O:37:\"Laravel\\Roster\\Ecosystems\\JsEcosystem\":3:{s:9:\"\0*\0byName\";a:6:{s:17:\"@tailwindcss/vite\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"@tailwindcss/vite\";s:10:\"\0*\0version\";s:5:\"4.0.0\";s:9:\"\0*\0source\";E:38:\"Laravel\\Roster\\Enums\\PackageSource:Npm\";s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^4.0.0\";s:7:\"\0*\0path\";s:75:\"C:\\project\\project-helpdesk\\helpdesk-backend\\node_modules\\@tailwindcss\\vite\";}s:12:\"concurrently\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"concurrently\";s:10:\"\0*\0version\";s:6:\"10.0.3\";s:9:\"\0*\0source\";r:1201;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:7:\"^10.0.3\";s:7:\"\0*\0path\";s:70:\"C:\\project\\project-helpdesk\\helpdesk-backend\\node_modules\\concurrently\";}s:19:\"laravel-vite-plugin\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"laravel-vite-plugin\";s:10:\"\0*\0version\";s:3:\"3.1\";s:9:\"\0*\0source\";r:1201;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^3.1\";s:7:\"\0*\0path\";s:77:\"C:\\project\\project-helpdesk\\helpdesk-backend\\node_modules\\laravel-vite-plugin\";}s:11:\"tailwindcss\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"tailwindcss\";s:10:\"\0*\0version\";s:5:\"4.0.0\";s:9:\"\0*\0source\";r:1201;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^4.0.0\";s:7:\"\0*\0path\";s:69:\"C:\\project\\project-helpdesk\\helpdesk-backend\\node_modules\\tailwindcss\";}s:4:\"vite\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:4:\"vite\";s:10:\"\0*\0version\";s:5:\"8.0.0\";s:9:\"\0*\0source\";r:1201;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^8.0.0\";s:7:\"\0*\0path\";s:62:\"C:\\project\\project-helpdesk\\helpdesk-backend\\node_modules\\vite\";}s:18:\"@laravel/multiplex\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"@laravel/multiplex\";s:10:\"\0*\0version\";s:5:\"0.4.1\";s:9:\"\0*\0source\";r:1201;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^0.4.1\";s:7:\"\0*\0path\";s:76:\"C:\\project\\project-helpdesk\\helpdesk-backend\\node_modules\\@laravel\\multiplex\";}}s:11:\"\0*\0packages\";O:32:\"Laravel\\Roster\\PackageCollection\":2:{s:8:\"\0*\0items\";a:6:{i:0;r:1198;i:1;r:1206;i:2;r:1214;i:3;r:1222;i:4;r:1230;i:5;r:1238;}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}s:17:\"\0*\0packageManager\";E:41:\"Laravel\\Roster\\Enums\\JsPackageManager:Bun\";}s:6:\"stacks\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:1:{i:0;E:30:\"Laravel\\Roster\\Enums\\Stack:Api\";}}s:21:\"browserTestFrameworks\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:0:{}}s:9:\"frontends\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:0:{}}s:6:\"agents\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:2:{i:0;E:37:\"Laravel\\Roster\\Enums\\Agent:ClaudeCode\";i:1;E:32:\"Laravel\\Roster\\Enums\\Agent:Codex\";}}s:7:\"editors\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:0:{}}}', 1790437347);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `kode` varchar(20) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `ketua` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`kode`, `nama`, `ketua`, `deskripsi`, `created_at`, `updated_at`) VALUES
('55201', 'Teknik Informatika', 'Dr. Achmad Musyafa, M.Kom.', 'Program Studi Informatika', NULL, '2026-09-22 10:35:43'),
('55202', 'SI', 'SI', 'SI', '2026-09-16 02:56:57', '2026-09-16 02:56:57'),
('55203', 'ilkom', 'ilkom', 'ilkom', '2026-09-16 02:57:16', '2026-09-16 02:57:16');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_bases`
--

CREATE TABLE `knowledge_bases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question` varchar(255) NOT NULL,
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`keywords`)),
  `answer` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `response_type` varchar(255) NOT NULL DEFAULT 'text',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `knowledge_bases`
--

INSERT INTO `knowledge_bases` (`id`, `question`, `keywords`, `answer`, `is_active`, `created_at`, `updated_at`, `response_type`, `options`) VALUES
(1, 'ajuan tiket', '[\"tiket\",\"laporan\",\"aduan\"]', 'Silahkan Klik \"Buat Tiket Baru\" \nKemudian Login Menggunakan akun UNPAM \n:)', 1, '2026-09-07 09:40:04', '2026-09-07 10:07:56', 'text', NULL),
(4, 'Rektor', '[\"Rektor\"]', 'Dr. Drs. E. Nurzaman AM, M.M., S.Si.\nRektor Universitas Pamulang', 1, '2026-09-07 09:59:09', '2026-09-07 19:34:24', 'text', NULL),
(5, 'Ketua yayasan', '[\"ketua yayasan\"]', 'Ketua Yayasan Universitas pamualang adalah \n\nDr. Pranoto, S.E., M.M.\n\napakah ada hal lain yang bisa kami Bantu :)', 1, '2026-09-07 10:04:17', '2026-09-07 19:33:56', 'text', NULL),
(7, 'Jumlah Mahasiswa', '[\"jumlah mahasiswa\"]', 'Universitas Pamulang (UNPAM) menyambut sekitar 28.000 mahasiswa baru pada PKKMB tahun 2026. [1] (https://akuntansiperpajakan.unpam.ac.id/berita/posts/pkkmb-unpam-2026-diikuti-28-ribu-mahasiswa-baru-tekankan-integritas-dan-adaptasi-teknologi)Detail Jumlah Peserta PKKMB UNPAM 2026Kampus Serang: Sekitar 6.000 mahasiswa baru. [1] (https://sasindo.unpam.ac.id/berita/posts/pkkmb-unpam-2026-diikuti-puluhan-ribu-mahasiswa-pimpinan-tekankan-adaptasi-integritas-dan-anti-kekerasan)Kampus Tangerang Selatan (Gelombang/Agenda):Reguler A: Sekitar 5.400 mahasiswa.Reguler CK & Reguler B: 3.108 mahasiswa.Reguler CS: Sekitar 5.200 mahasiswa. [1] (https://sasindo.unpam.ac.id/berita/posts/pkkmb-unpam-2026-diikuti-puluhan-ribu-mahasiswa-pimpinan-tekankan-adaptasi-integritas-dan-anti-kekerasan)Secara Daring: Sekitar 3.000 mahasiswa. [1] (https://sasindo.unpam.ac.id/berita/posts/pkkmb-unpam-2026-diikuti-puluhan-ribu-mahasiswa-pimpinan-tekankan-adaptasi-integritas-dan-anti-kekerasan)Berdasarkan data historis PDDikti sebelumnya, UNPAM terus menjadi salah satu perguruan tinggi swasta dengan pertumbuhan jumlah mahasiswa yang sangat signifikan di Indonesia. [1] (https://www.kompas.id/artikel/kampus-swasta-ini-tetap-bisa-memikat-mahasiswa-baru-di-tengah-dominasi-perguruan-tinggi-negeri)', 1, '2026-09-07 10:30:27', '2026-09-07 10:30:27', 'text', NULL),
(8, 'Sekretaris', '[\"sekretaris\"]', 'Lembaga Sekretariat Rektorat Universitas Pamulang dipimpin oleh Dr. (Li) Eka Margianti Sagimin, S.S., M.Pd. selaku Kepala Sekretariat Rektorat', 1, '2026-09-07 19:29:20', '2026-09-07 19:33:23', 'text', NULL),
(9, 'Biaya Kuliah', '[\"biaya kuliah\"]', 'Rincian Biaya Kuliah S-1 (Wilayah Tangerang Selatan)Berikut adalah perkiraan rincian biaya kuliah per komponen di UNPAM:Formulir Pendaftaran: Rp100.000 (diskon 50% untuk gelombang tertentu) [1] (https://pmb.unpam.ac.id/biaya-kuliah)Jaket Almamater + KTM: Rp250.000 (dibayar sekali setelah lulus seleksi) [1] (https://pmb.unpam.ac.id/biaya-kuliah)Registrasi Awal Semester: Rp250.000 (dibayar setiap awal semester) [1] (https://pmb.unpam.ac.id/biaya-kuliah)Biaya Kuliah (Per Semester):Reguler A & B: Rp1.500.000 (bisa diangsur sekitar Rp250.000/angsuran)Reguler CS & CK (Kelas Karyawan/Sabtu-Kamis): Rp2.400.000 (bisa diangsur sekitar Rp400.000/angsuran) [1] (https://pmb.unpam.ac.id/biaya-kuliah)Biaya Praktikum: Rp200.000 hingga Rp300.000 per semester (khusus Fakultas Teknik, MIPA, Ilmu Komputer, Ilmu Komunikasi & Desain, serta Pendidikan Jasmani) [1] (https://pmb.unpam.ac.id/biaya-kuliah)Biaya UTS: Rp300.000 (Reguler A & B) atau Rp400.000 (Reguler CS & CK) [1] (https://pmb.unpam.ac.id/biaya-kuliah)Biaya UAS: Rp300.000 (Reguler A & B) atau Rp400.000 (Reguler CS & CK) [1] (<a href=\"https://pmb.unpam.ac.id/biaya-kuliah\"></a>)Catatan: UNPAM tidak mengenakan biaya uang gedung. Rincian biaya di atas dapat sedikit berbeda untuk kampus cabang seperti di Serang atau program Pascasarjana (S-2). [1] (https://pmb.unpam.ac.id/biaya-kuliah)', 1, '2026-09-07 20:03:43', '2026-09-07 20:05:51', 'text', NULL),
(10, 'helpdesk', '[\"helpdesk\"]', 'Silakan klik [di sini](https://helpdesk.unpam.ac.id) untuk mengajukan tiket.', 1, '2026-09-07 20:08:32', '2026-09-07 20:08:32', 'text', NULL),
(11, 'link unpam', '[\"link unpam\"]', 'unpam.ac.id', 1, '2026-09-07 20:17:30', '2026-09-07 20:40:14', 'text', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_09_04_011954_create_personal_access_tokens_table', 1),
(5, '2026_09_06_050647_create_roles_table', 2),
(12, '2026_09_07_155609_create_knowledge_bases_table', 6),
(15, 'tb_roles', 7),
(16, 'mig_department', 8),
(18, 'mig_chat', 9),
(19, 'update_k', 10),
(20, 'mig_tickets_add_rating', 11),
(21, '2026_09_26_000003_create_unanswered_chat_questions_table', 12);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'auth_token', 'f9bc555c0bc0dcbbe60196f91db398b68799fa30678f2937b8b9a7e143966d39', '[\"*\"]', NULL, NULL, '2026-09-05 05:46:22', '2026-09-05 05:46:22'),
(3, 'App\\Models\\User', 1, 'auth_token', 'd5cb1e9b0424ffb499463ff2af89081679082d1114f6509f0880475fa275397e', '[\"*\"]', '2026-09-05 05:57:51', NULL, '2026-09-05 05:57:03', '2026-09-05 05:57:51'),
(10, 'App\\Models\\User', 1, 'auth_token', '7bc2c974a6993f88b28d571bd2bc833f15906498320e8cec4aaaea81a671320d', '[\"*\"]', '2026-09-05 07:49:22', NULL, '2026-09-05 07:49:20', '2026-09-05 07:49:22'),
(12, 'App\\Models\\User', 2, 'auth_token', 'a9ca0d2a8f1379ceaa06d51df0824b15be0fb30e0ad53c179942177b0880533a', '[\"*\"]', NULL, NULL, '2026-09-05 17:26:57', '2026-09-05 17:26:57'),
(14, 'App\\Models\\User', 2, 'auth_token', '7c345691d0e64b38b45b12f4466a0ec23ff6828770f2fcfbc057980f390f5bc8', '[\"*\"]', '2026-09-05 21:26:56', NULL, '2026-09-05 17:43:06', '2026-09-05 21:26:56'),
(15, 'App\\Models\\User', 1, 'auth_token', '57825f90399a9be0142a2fa9c98ce5b2252a8cb23d80dc5dd99b7795237fc956', '[\"*\"]', '2026-09-05 21:28:47', NULL, '2026-09-05 21:28:45', '2026-09-05 21:28:47'),
(17, 'App\\Models\\User', 1, 'auth_token', 'f61f875759a4b7fa35a85cd83c650de6e796ed3dadf8b02478dea20d77bfde11', '[\"*\"]', '2026-09-05 21:32:40', NULL, '2026-09-05 21:32:28', '2026-09-05 21:32:40'),
(20, 'App\\Models\\User', 2, 'auth_token', 'fab1eacb66be8ddfbb89abab740f7583295db3edb6db5965a5e472751ba227fb', '[\"*\"]', '2026-09-05 21:55:27', NULL, '2026-09-05 21:35:58', '2026-09-05 21:55:27'),
(22, 'App\\Models\\User', 2, 'auth_token', '252a422bba5ad6cfa6df004a28b0c5969cdc4ec39d31a0e3d59269abef601bcd', '[\"*\"]', '2026-09-05 21:55:27', NULL, '2026-09-05 21:48:37', '2026-09-05 21:55:27'),
(23, 'App\\Models\\User', 2, 'auth_token', '9da9aa02cb62517b175f630f2bc8df74b9eb0f9e82a807996231ce66f2ca22e8', '[\"*\"]', '2026-09-05 21:56:26', NULL, '2026-09-05 21:56:19', '2026-09-05 21:56:26'),
(24, 'App\\Models\\User', 1, 'auth_token', '68b548ec5f209a9b9f51125693f6e27a3d074954d8a5fc05887e409180f4827d', '[\"*\"]', '2026-09-05 22:01:16', NULL, '2026-09-05 22:01:13', '2026-09-05 22:01:16'),
(27, 'App\\Models\\User', 2, 'auth_token', '94dbce18904602220de09c44937abcff3b23dad8666e9d1da0d8529ff16543cf', '[\"*\"]', '2026-09-06 00:06:08', NULL, '2026-09-06 00:06:06', '2026-09-06 00:06:08'),
(32, 'App\\Models\\User', 2, 'auth_token', 'fe56ae101e6792cd143eedcf616bd1d8df6780a730b1ee79399416a80d923628', '[\"*\"]', '2026-09-06 01:51:34', NULL, '2026-09-06 01:37:04', '2026-09-06 01:51:34'),
(38, 'App\\Models\\User', 1, 'auth_token', '9599ebd43f3c04b8cd2471923f3b9bf2081ff69a6697fb7162f360c4718cd882', '[\"*\"]', '2026-09-06 06:54:13', NULL, '2026-09-06 06:46:22', '2026-09-06 06:54:13'),
(39, 'App\\Models\\User', 1, 'auth_token', '1777e0d0fd64ec3a8e7b185a91c17da8c7117dc939fc277b5facb2bb1aaaca9d', '[\"*\"]', '2026-09-06 07:35:51', NULL, '2026-09-06 07:35:48', '2026-09-06 07:35:51'),
(43, 'App\\Models\\User', 1, 'auth_token', '89745e3a7bb80f3240d9baa7b9c3713a6d68cddbc39a11a3983e2d37d2f1c2d1', '[\"*\"]', '2026-09-06 08:06:45', NULL, '2026-09-06 07:56:21', '2026-09-06 08:06:45'),
(46, 'App\\Models\\User', 1, 'auth_token', 'a07b0df8fcd97448b48a4633999ce5cc0db9ea9ff60771de042d98f0eab89903', '[\"*\"]', '2026-09-06 08:31:08', NULL, '2026-09-06 08:24:47', '2026-09-06 08:31:08'),
(54, 'App\\Models\\User', 2, 'auth_token', 'cdbdcbc9a8bbaefd4bbd36075db7eb3383efbbe8752b416d8f25aac606941e7b', '[\"*\"]', '2026-09-06 18:59:37', NULL, '2026-09-06 18:59:32', '2026-09-06 18:59:37'),
(55, 'App\\Models\\User', 3, 'auth_token', '5c66bbd03d26bc600e26a6aaa1f19ff5adb93e282a2e617ad788fa403159872b', '[\"*\"]', NULL, NULL, '2026-09-06 19:29:14', '2026-09-06 19:29:14'),
(56, 'App\\Models\\User', 1, 'auth_token', 'c9a72fe867a32df70d136d7aa3f4e45570040622c1afb4bb4f734f2309f1225f', '[\"*\"]', NULL, NULL, '2026-09-06 19:30:16', '2026-09-06 19:30:16'),
(59, 'App\\Models\\User', 1, 'auth_token', 'bb21f7f6fab123a3220a3a32249b37b30632264fcd704927b14afaf69f86cd31', '[\"*\"]', '2026-09-06 21:10:34', NULL, '2026-09-06 19:58:04', '2026-09-06 21:10:34'),
(61, 'App\\Models\\User', 1, 'auth_token', '59e521fa58ee7d4085cb041f9cf203be249df81ba8ff9b9739a76798d7b62327', '[\"*\"]', '2026-09-06 21:22:26', NULL, '2026-09-06 21:16:29', '2026-09-06 21:22:26'),
(68, 'App\\Models\\User', 3, 'auth_token', 'dc32080554e2404a129b0c95897209fdd279d87318a4c88f87e70088810938c9', '[\"*\"]', '2026-09-07 00:29:40', NULL, '2026-09-07 00:24:29', '2026-09-07 00:29:40'),
(69, 'App\\Models\\User', 1, 'auth_token', '464c320b0daa833c5e9aed5b257229d73b941d4192ce02a42c69a02dfa202c29', '[\"*\"]', '2026-09-07 01:01:14', NULL, '2026-09-07 01:01:11', '2026-09-07 01:01:14'),
(70, 'App\\Models\\User', 1, 'auth_token', '224ecdbcb938ad0a9aa43d751de7eaba4d2f34dc0389ce5fdfd536cec5f06e55', '[\"*\"]', '2026-09-07 01:02:05', NULL, '2026-09-07 01:02:02', '2026-09-07 01:02:05'),
(73, 'App\\Models\\User', 1, 'auth_token', '053629043556d582a32f57821154e2e92c585b0bede587ed8f99ceb23e81f769', '[\"*\"]', '2026-09-07 01:40:47', NULL, '2026-09-07 01:40:44', '2026-09-07 01:40:47'),
(74, 'App\\Models\\User', 1, 'auth_token', 'b09b1318aa3498c2f69104786cd60dabc753a859bb90601ef44fcb6c63257b2d', '[\"*\"]', '2026-09-07 01:41:04', NULL, '2026-09-07 01:41:01', '2026-09-07 01:41:04'),
(75, 'App\\Models\\User', 1, 'auth_token', 'c7b0757d7226ae764e4adcbd76b3ab08df27fa54b2bf06c99e2badc5b5751d2b', '[\"*\"]', NULL, NULL, '2026-09-07 01:42:45', '2026-09-07 01:42:45'),
(76, 'App\\Models\\User', 1, 'auth_token', 'e14111f5ce64ba3e1a83282993a2712a87b3fe66588db1a059b510e25a93dfc6', '[\"*\"]', '2026-09-07 04:48:25', NULL, '2026-09-07 01:55:57', '2026-09-07 04:48:25'),
(77, 'App\\Models\\User', 1, 'auth_token', '121c46b6d57348846e7149bb72f286a04fb6aac1c804c0c5a37307f415fd1940', '[\"*\"]', '2026-09-07 04:48:33', NULL, '2026-09-07 02:40:54', '2026-09-07 04:48:33'),
(87, 'App\\Models\\User', 1, 'auth_token', '0409b5a77b0eaa45da6f299088268297ab4d9a6b9f47e179ac3bf109bfa228d8', '[\"*\"]', NULL, NULL, '2026-09-07 07:24:48', '2026-09-07 07:24:48'),
(88, 'App\\Models\\User', 1, 'auth_token', '22ed9e6b279657839682e4d8d4503e63dc4a844acb58eb6740c10b84f05d3940', '[\"*\"]', NULL, NULL, '2026-09-07 07:26:12', '2026-09-07 07:26:12'),
(89, 'App\\Models\\User', 1, 'auth_token', '71f42accc68a185217c88988af1128b2ca0f79d66ba5d949c53d1e5b14faeb46', '[\"*\"]', NULL, NULL, '2026-09-07 07:28:14', '2026-09-07 07:28:14'),
(90, 'App\\Models\\User', 1, 'auth_token', '5fa4cce4d6a62abbf26e7ba71ccfd934ba920ae7a826622e75c868eb3deb791a', '[\"*\"]', NULL, NULL, '2026-09-07 07:31:03', '2026-09-07 07:31:03'),
(94, 'App\\Models\\User', 1, 'auth_token', 'e40d815ed38b59af920bace45198136c5cab0264614c064a3e7cf259560f5f3e', '[\"*\"]', '2026-09-07 07:50:03', NULL, '2026-09-07 07:40:05', '2026-09-07 07:50:03'),
(97, 'App\\Models\\User', 1, 'auth_token', '62c39129ed56d622fe1eb9a1fdeae770baa94197f7cce01876da9868ddd4c9ef', '[\"*\"]', '2026-09-07 08:48:56', NULL, '2026-09-07 08:48:34', '2026-09-07 08:48:56'),
(98, 'App\\Models\\User', 1, 'auth_token', '92e1825148ba9740a118055a7203370a250a5d1c648d9a9492c27fb3f3d900b9', '[\"*\"]', '2026-09-07 08:52:47', NULL, '2026-09-07 08:49:56', '2026-09-07 08:52:47'),
(102, 'App\\Models\\User', 1, 'auth_token', '059bcf5103dd0d2020213ee48844b08d2d76915984c844be014c1d803ee68b8d', '[\"*\"]', '2026-09-07 09:12:48', NULL, '2026-09-07 09:12:44', '2026-09-07 09:12:48'),
(108, 'App\\Models\\User', 1, 'auth_token', '1951600a17a466f98eb9c7534b18d9b76f455b02a3d2e70a13e84a8831d11740', '[\"*\"]', '2026-09-07 09:55:54', NULL, '2026-09-07 09:45:47', '2026-09-07 09:55:54'),
(112, 'App\\Models\\User', 1, 'auth_token', '185f64637bee7245aa02040f8931649ee67b39ae445b186c8b0eb206af0fa017', '[\"*\"]', '2026-09-07 10:18:47', NULL, '2026-09-07 10:18:32', '2026-09-07 10:18:47'),
(113, 'App\\Models\\User', 1, 'auth_token', 'c9453818cceb9ac5d26d41efe2f85aea9ee08110e4892ff501441bf0c56a34c4', '[\"*\"]', '2026-09-07 10:35:38', NULL, '2026-09-07 10:29:11', '2026-09-07 10:35:38'),
(115, 'App\\Models\\User', 1, 'auth_token', '2e4dfcd489787055616163bb5a65c0abad56774a92593425446f16a979cb808d', '[\"*\"]', '2026-09-07 19:34:25', NULL, '2026-09-07 19:11:16', '2026-09-07 19:34:25'),
(116, 'App\\Models\\User', 4, 'auth_token', 'b684aedb75cbd68998672cc1b1fbf47e1d87dbe65c6544e5950c2d453610002f', '[\"*\"]', '2026-09-07 19:32:03', NULL, '2026-09-07 19:12:29', '2026-09-07 19:32:03'),
(118, 'App\\Models\\User', 1, 'auth_token', '5a39f297c08c5a3ccf9faf9de964dcdeb399b2c498895c0867ad55fd2b714ebb', '[\"*\"]', '2026-09-07 20:42:22', NULL, '2026-09-07 20:02:31', '2026-09-07 20:42:22'),
(120, 'App\\Models\\User', 1, 'auth_token', 'd11768feeb992722670047d877f704769df2c14e4436472cd4d38494bd327974', '[\"*\"]', '2026-09-07 22:00:18', NULL, '2026-09-07 20:47:08', '2026-09-07 22:00:18'),
(121, 'App\\Models\\User', 4, 'auth_token', '9e432ce7044ae4b7abb5be1248faa7a0c4c33f796784b849e4afea1980ac5c08', '[\"*\"]', '2026-09-07 22:26:02', NULL, '2026-09-07 22:01:27', '2026-09-07 22:26:02'),
(122, 'App\\Models\\User', 4, 'auth_token', '48dc081239e0a5fad7d95516fdf24cf36e7f46e26d47ed384807bb960db3902e', '[\"*\"]', '2026-09-08 04:35:37', NULL, '2026-09-08 04:35:08', '2026-09-08 04:35:37'),
(127, 'App\\Models\\User', 1, 'auth_token', '25fae5c5295a24e58a4704e3d5d3d21c1089a147ac62ad02036cfe5cda0a9c72', '[\"*\"]', '2026-09-08 10:28:11', NULL, '2026-09-08 10:16:13', '2026-09-08 10:28:11'),
(129, 'App\\Models\\User', 1, 'auth_token', 'de78eecd886f7b4cdf766f2e8fb8e1abbf3ded2e7c0e61c1896c7ba406daa34f', '[\"*\"]', NULL, NULL, '2026-09-08 11:21:20', '2026-09-08 11:21:20'),
(134, 'App\\Models\\User', 1, 'auth_token', '2312c44ee6135bd7d797f69ae42911e7a358b8b77524a1a089700dcd39baeb6e', '[\"*\"]', '2026-09-08 12:38:20', NULL, '2026-09-08 12:23:46', '2026-09-08 12:38:20'),
(135, 'App\\Models\\User', 4, 'auth_token', 'c1f9222756e6ef659f6f10b355d872a74f5dedcf2685a74cfa3a8e08b9e0c257', '[\"*\"]', '2026-09-08 12:47:41', NULL, '2026-09-08 12:47:40', '2026-09-08 12:47:41'),
(136, 'App\\Models\\User', 4, 'auth_token', 'a60389b5b97ffbf10bfe3f1fe77e63ebdf6b1bf7271bef700acdd4a89440fd1a', '[\"*\"]', '2026-09-08 12:47:58', NULL, '2026-09-08 12:47:57', '2026-09-08 12:47:58'),
(137, 'App\\Models\\User', 4, 'auth_token', '633b7313ccf8ea1924c4705909bb372c9e409d169eb0da31581efb7f97d84d03', '[\"*\"]', '2026-09-08 12:48:45', NULL, '2026-09-08 12:48:43', '2026-09-08 12:48:45'),
(138, 'App\\Models\\User', 4, 'auth_token', 'b4bf874148fb7f4607cb628400a01638d754e7e5e50c00b412ce0a0fa0f92130', '[\"*\"]', '2026-09-08 12:51:13', NULL, '2026-09-08 12:51:11', '2026-09-08 12:51:13'),
(140, 'App\\Models\\User', 4, 'auth_token', '7644f3d79413de3edeba11fad1eda55f6e682e17fe4f3d8ce4d411e43509e4a3', '[\"*\"]', NULL, NULL, '2026-09-08 12:56:58', '2026-09-08 12:56:58'),
(143, 'App\\Models\\User', 4, 'auth_token', '487fa75d528aad0a8feecfb8052547d5dc097c771e70e03f9866dee91d0a4be7', '[\"*\"]', '2026-09-08 13:15:47', NULL, '2026-09-08 13:15:41', '2026-09-08 13:15:47'),
(144, 'App\\Models\\User', 4, 'auth_token', 'd7972267126fca19f18e25a6d855b0ec872941cf1930c374c77199771eaf1ada', '[\"*\"]', '2026-09-08 13:16:34', NULL, '2026-09-08 13:16:28', '2026-09-08 13:16:34'),
(147, 'App\\Models\\User', 4, 'auth_token', '0bc4c23623971dd2999959f22a02be9fd239b48319526975bdc5edddb1d28236', '[\"*\"]', '2026-09-08 13:41:01', NULL, '2026-09-08 13:40:55', '2026-09-08 13:41:01'),
(148, 'App\\Models\\User', 4, 'auth_token', '604041b4c95ee93fc649fab4d83b97e8e290fd7ea8f8aa934980f5c3c9982f98', '[\"*\"]', '2026-09-08 13:54:55', NULL, '2026-09-08 13:41:10', '2026-09-08 13:54:55'),
(151, 'App\\Models\\User', 4, 'auth_token', '802aafd0ed21afb38d676e6f2ff85a81c8aa1af56881e0ad27941c2fdbe44af8', '[\"*\"]', '2026-09-08 14:02:57', NULL, '2026-09-08 14:02:54', '2026-09-08 14:02:57'),
(152, 'App\\Models\\User', 4, 'auth_token', '033626a6d1e6356d388464453c80a6aa08b5643d899b5a4f4273e5a0f9c96fc8', '[\"*\"]', '2026-09-08 14:03:19', NULL, '2026-09-08 14:03:17', '2026-09-08 14:03:19'),
(153, 'App\\Models\\User', 4, 'auth_token', 'fbe17bb75b89325932396d8dc6a91f3ac3c59cf0d797768bdaca07d128e9ea01', '[\"*\"]', '2026-09-08 14:06:25', NULL, '2026-09-08 14:06:23', '2026-09-08 14:06:25'),
(154, 'App\\Models\\User', 4, 'auth_token', 'd3ad392cfad9dffd9bf5856d994ee89b3b324dd7ac1aaac0609a981e6df7a3f3', '[\"*\"]', '2026-09-08 14:06:54', NULL, '2026-09-08 14:06:52', '2026-09-08 14:06:54'),
(169, 'App\\Models\\User', 1, 'auth_token', '5dd3df1c79929a7c74a5e155d54fa4dc669ae1f0799ccbe6b7820c46a046bf71', '[\"*\"]', '2026-09-08 20:22:46', NULL, '2026-09-08 15:28:43', '2026-09-08 20:22:46'),
(181, 'App\\Models\\User', 1, 'auth_token', 'c15b29e06bccfe1670f296b1b66491d2ea6835c339321e335c10e34c4f6dc1ec', '[\"*\"]', '2026-09-09 00:56:33', NULL, '2026-09-09 00:24:57', '2026-09-09 00:56:33'),
(182, 'App\\Models\\User', 4, 'auth_token', '022ee21597bb96455aa1733c21fe2c9d5931d55278a5d34e47e371b6b0fcd5e7', '[\"*\"]', '2026-09-09 00:33:50', NULL, '2026-09-09 00:30:54', '2026-09-09 00:33:50'),
(183, 'App\\Models\\User', 4, 'auth_token', 'f63d4087b30305320047b7ad0cf605a46cf1967eb043467fe7d42538401ad284', '[\"*\"]', '2026-09-09 00:56:22', NULL, '2026-09-09 00:33:57', '2026-09-09 00:56:22'),
(184, 'App\\Models\\User', 4, 'auth_token', 'a56d884c549352c8bdd91f6f5a583e69793098c87921400829a7418755e34fc4', '[\"*\"]', '2026-09-09 00:34:22', NULL, '2026-09-09 00:34:15', '2026-09-09 00:34:22'),
(185, 'App\\Models\\User', 1, 'auth_token', 'da4ca1a9b56bd44d279e47e00fb2187e84d6e49c245864a2defecd1e2f2415b3', '[\"*\"]', '2026-09-09 01:50:35', NULL, '2026-09-09 01:43:22', '2026-09-09 01:50:35'),
(186, 'App\\Models\\User', 9, 'sso-token', '9e340dfac3dc8da9c33b37b638de7619450d8d48dccc93e59cea46286f505f6f', '[\"*\"]', NULL, NULL, '2026-09-09 02:40:10', '2026-09-09 02:40:10'),
(187, 'App\\Models\\User', 9, 'sso-token', 'f8edadbfe348f90304c028d9c38b2bbb850d15d5c58361913aa3972441fb5d94', '[\"*\"]', NULL, NULL, '2026-09-09 02:42:11', '2026-09-09 02:42:11'),
(188, 'App\\Models\\User', 9, 'sso-token', 'ce93183d9457787186688ec59595847b558b9c053bf380af84a2a4f4a07fb8f7', '[\"*\"]', NULL, NULL, '2026-09-09 02:42:22', '2026-09-09 02:42:22'),
(190, 'App\\Models\\User', 10, 'sso-token', '6717e34c01bcf748988bfacf2e7d1a8827bc5432733d4edb471b22c91e458259', '[\"*\"]', '2026-09-09 03:04:32', NULL, '2026-09-09 03:04:26', '2026-09-09 03:04:32'),
(191, 'App\\Models\\User', 11, 'sso-token', 'b70037be202cc8b5fb848d2d84f5cbe95baeaa3d91e0d85f7c689ee9edf97db7', '[\"*\"]', '2026-09-09 03:19:13', NULL, '2026-09-09 03:19:08', '2026-09-09 03:19:13'),
(200, 'App\\Models\\User', 2, 'sso-token', '769983ac00bc09c4d4beb95d337d1db88b52c4ea3aac6f91f3ac5d5947f878e8', '[\"*\"]', '2026-09-09 07:01:08', NULL, '2026-09-09 04:32:29', '2026-09-09 07:01:08'),
(204, 'App\\Models\\User', 2, 'sso-token', '82eb286dbe9aa6ec09824cd392176bf3e94afc1b35f324a3023b664397802be8', '[\"*\"]', '2026-09-09 07:54:56', NULL, '2026-09-09 07:30:39', '2026-09-09 07:54:56'),
(215, 'App\\Models\\User', 9, 'sso-token', 'cdebbc07bb49cf7cea66d48b14a11f70c246e03ed1d7c78c56990bc2a9ea108c', '[\"*\"]', '2026-09-09 19:44:15', NULL, '2026-09-09 19:44:11', '2026-09-09 19:44:15'),
(216, 'App\\Models\\User', 10, 'sso-token', '14d4ca082ce3aa8d0f12086e976ff05e04bcb4735df916ec0ac04c5e922b30ae', '[\"*\"]', '2026-09-09 19:47:39', NULL, '2026-09-09 19:47:25', '2026-09-09 19:47:39'),
(222, 'App\\Models\\User', 2, 'sso-token', 'b6560eb5832d4a7faaaadb3ea79c2db601e4ba359decc006bf3e27f5d55118a2', '[\"*\"]', '2026-09-10 23:57:45', NULL, '2026-09-10 23:40:38', '2026-09-10 23:57:45'),
(223, 'App\\Models\\User', 2, 'sso-token', 'd2508d4938fa73d0ef310b956f0f5d549fa1aec1972737c177708c3f5207fad3', '[\"*\"]', '2026-09-11 02:44:31', NULL, '2026-09-11 00:37:17', '2026-09-11 02:44:31'),
(225, 'App\\Models\\User', 2, 'sso-token', 'bb042031b8fe2185eb9975ef208803163d424765fc1e622d3bf36ac916f91506', '[\"*\"]', '2026-09-11 02:28:16', NULL, '2026-09-11 00:56:50', '2026-09-11 02:28:16'),
(226, 'App\\Models\\User', 2, 'sso-token', '131f0fdb9f18382a345d61fa98705f61da9fba51cb30d23f2963cc12625788f3', '[\"*\"]', '2026-09-11 05:02:36', NULL, '2026-09-11 04:47:04', '2026-09-11 05:02:36'),
(238, 'App\\Models\\User', 2, 'sso-token', 'c908a19aed6cf617ddde3c7a9e6659ddfbc8de32027eb4c6adfd6b0c8b656a79', '[\"*\"]', '2026-09-11 08:09:25', NULL, '2026-09-11 08:00:51', '2026-09-11 08:09:25'),
(239, 'App\\Models\\User', 2, 'sso-token', 'e1208821ba92f61f153e7d5eaf8a8e08c6039058782adc29c157a685bbfd4e3a', '[\"*\"]', '2026-09-11 09:11:57', NULL, '2026-09-11 09:02:56', '2026-09-11 09:11:57'),
(248, 'App\\Models\\User', 2, 'sso-token', 'ea7a4790ae49f2e7fa824d838e4123d00964171a65b23a6bd9ec77fff033a755', '[\"*\"]', '2026-09-11 20:28:22', NULL, '2026-09-11 18:49:30', '2026-09-11 20:28:22'),
(252, 'App\\Models\\User', 2, 'sso-token', '9e74592a12869aee1bf40c1bee8bda8c6efb91acb97c9760a1559c96b5c88c59', '[\"*\"]', '2026-09-11 20:37:48', NULL, '2026-09-11 20:37:44', '2026-09-11 20:37:48'),
(254, 'App\\Models\\User', 3, 'sso-token', 'fcc6e9f3954f36feb96ed48cf66511f2fce162a1b6018c134f107558cf5a76a6', '[\"*\"]', '2026-09-12 00:13:06', NULL, '2026-09-11 20:54:19', '2026-09-12 00:13:06'),
(256, 'App\\Models\\User', 3, 'sso-token', '1bc518ed202474193e1c55d32a21bf51c41227a7cc75f017cea30a21e1e25e28', '[\"*\"]', '2026-09-12 05:55:15', NULL, '2026-09-12 02:50:26', '2026-09-12 05:55:15'),
(257, 'App\\Models\\User', 3, 'sso-token', '0991253bee16527f36bc301b42711ea157cc95a4b436be407de953ccd6ce70ae', '[\"*\"]', '2026-09-14 21:15:45', NULL, '2026-09-14 21:12:41', '2026-09-14 21:15:45'),
(275, 'App\\Models\\User', 8, 'sso-token', '7a378173476d7773aa698b82559b528b23b0c90d0437a9aa4da030e079480333', '[\"*\"]', '2026-09-15 01:59:24', NULL, '2026-09-15 01:45:09', '2026-09-15 01:59:24'),
(283, 'App\\Models\\User', 6, 'sso-token', '0e179a981adc5c6d2dd78190bb422a7cb496bff701ba4043ffee3ff1f7a96981', '[\"*\"]', '2026-09-15 05:29:04', NULL, '2026-09-15 03:20:00', '2026-09-15 05:29:04'),
(284, 'App\\Models\\User', 3, 'sso-token', '0cec78a854651f8b846371394460e13d42a7d8ed218ba83a3ffb91cd32194e06', '[\"*\"]', '2026-09-15 05:42:59', NULL, '2026-09-15 05:29:03', '2026-09-15 05:42:59'),
(285, 'App\\Models\\User', 3, 'sso-token', '2e6a415b6a02b4539440cc8105b6661b9a88d4950e8bcc2c2c59178d36931be5', '[\"*\"]', '2026-09-15 06:52:48', NULL, '2026-09-15 06:19:18', '2026-09-15 06:52:48'),
(286, 'App\\Models\\User', 11, 'auth_token', '358ada5c2eecfefadb99646f9561b34827fb689bb6f05f033f5daef315ae0694', '[\"*\"]', NULL, NULL, '2026-09-15 08:24:27', '2026-09-15 08:24:27'),
(289, 'App\\Models\\User', 12, 'sso-token', '45606ecf53d70728a17f30ddde846c7cfee76679298c57ef6963f14b52b977db', '[\"*\"]', '2026-09-15 08:46:38', NULL, '2026-09-15 08:42:08', '2026-09-15 08:46:38'),
(290, 'App\\Models\\User', 10, 'sso-token', '8b533284f87dd5de11a0009b89b1ab10568da183316cb7daf208813b19da3db5', '[\"*\"]', '2026-09-15 08:47:03', NULL, '2026-09-15 08:47:01', '2026-09-15 08:47:03'),
(291, 'App\\Models\\User', 10, 'sso-token', 'b4f423a0941775e6cc597e5bf7343f3ee23c8318af0f96f6f19ac60799dc154b', '[\"*\"]', '2026-09-15 08:50:36', NULL, '2026-09-15 08:50:34', '2026-09-15 08:50:36'),
(292, 'App\\Models\\User', 10, 'sso-token', 'b62021408a9bb1f1bf27b67443b833d49172e75210a7409ce722fbc82dc7309f', '[\"*\"]', '2026-09-15 09:04:46', NULL, '2026-09-15 08:51:41', '2026-09-15 09:04:46'),
(293, 'App\\Models\\User', 10, 'sso-token', '6d07f594f35c779793d17a6ab0fb5d92c3f0c8f2d971680f540db47feff2a251', '[\"*\"]', '2026-09-15 09:27:32', NULL, '2026-09-15 09:05:02', '2026-09-15 09:27:32'),
(295, 'App\\Models\\User', 12, 'sso-token', '65726633200c8dec953379aba7bc8ca846a8b010101079e7b54c72e2dfa30507', '[\"*\"]', '2026-09-15 09:35:31', NULL, '2026-09-15 09:32:53', '2026-09-15 09:35:31'),
(297, 'App\\Models\\User', 12, 'sso-token', '5b7d90f033124f92f4bfc6b2cc9ef4bf39d90d8e60916b6638300d20d7353722', '[\"*\"]', '2026-09-15 09:44:56', NULL, '2026-09-15 09:37:59', '2026-09-15 09:44:56'),
(298, 'App\\Models\\User', 12, 'sso-token', 'dabb9c0c4d2a70e1ad87e4875026689e55b656663172665b1c9563ea11d6e8c0', '[\"*\"]', '2026-09-15 09:49:29', NULL, '2026-09-15 09:45:12', '2026-09-15 09:49:29'),
(299, 'App\\Models\\User', 12, 'sso-token', '8b0f7d1c0f9a8a2e8a16f39633f8de079faef397d1a7d2eeac5078dccd4d3275', '[\"*\"]', '2026-09-15 10:07:30', NULL, '2026-09-15 09:49:51', '2026-09-15 10:07:30'),
(300, 'App\\Models\\User', 12, 'sso-token', '0ed8135405d218b2a2932aa986a42fec65b220275b4d39822a491031e25b05c7', '[\"*\"]', '2026-09-15 10:08:25', NULL, '2026-09-15 10:08:23', '2026-09-15 10:08:25'),
(306, 'App\\Models\\User', 12, 'sso-token', '5809e72a682529f913eab65fe134667ef57e030e1f02be6ed04656739155d0ee', '[\"*\"]', '2026-09-15 10:30:46', NULL, '2026-09-15 10:30:44', '2026-09-15 10:30:46'),
(307, 'App\\Models\\User', 10, 'sso-token', '5cf43bd03c3037d210d0774d68e0f6c2f4f4202ac13afe5880b871367653cc1e', '[\"*\"]', '2026-09-15 10:47:00', NULL, '2026-09-15 10:42:53', '2026-09-15 10:47:00'),
(311, 'App\\Models\\User', 12, 'sso-token', '341c1767012963e8891b35f56e4fd2b2225ef59781039cc833989fe8bbff438d', '[\"*\"]', '2026-09-15 20:10:08', NULL, '2026-09-15 18:43:22', '2026-09-15 20:10:08'),
(312, 'App\\Models\\User', 12, 'sso-token', 'fb6d09ec0f9b0f0feb5e8541d7c75329ae5d838816dc937d88baa6b93235917e', '[\"*\"]', '2026-09-15 20:16:41', NULL, '2026-09-15 20:10:18', '2026-09-15 20:16:41'),
(317, 'App\\Models\\User', 12, 'sso-token', '2b9fa703ec326f9d19370973a2e665ccb819c563e76014ffb298bbb16fc7040b', '[\"*\"]', '2026-09-15 21:44:09', NULL, '2026-09-15 21:11:35', '2026-09-15 21:44:09'),
(318, 'App\\Models\\User', 12, 'sso-token', '5c751dc58f7c60ad6e15932881cc4308bdc92f22fe85971abe45e8198dd5d07e', '[\"*\"]', '2026-09-15 21:45:15', NULL, '2026-09-15 21:44:18', '2026-09-15 21:45:15'),
(319, 'App\\Models\\User', 12, 'sso-token', '650668f13495a5a9898d2998031f3082e0ca7cf5821cbec346d99d70ed43d5e8', '[\"*\"]', '2026-09-15 22:20:13', NULL, '2026-09-15 21:45:31', '2026-09-15 22:20:13'),
(320, 'App\\Models\\User', 12, 'sso-token', '0fc6a4308e269270cac49b5853a80fa0e792b687399f74330376a43fa01283ee', '[\"*\"]', '2026-09-15 22:33:41', NULL, '2026-09-15 22:20:28', '2026-09-15 22:33:41'),
(321, 'App\\Models\\User', 12, 'sso-token', '14cacfc4d2f7f5a337160ea5c518739b14bc458a898d962a20812a8584997550', '[\"*\"]', '2026-09-15 22:37:17', NULL, '2026-09-15 22:34:19', '2026-09-15 22:37:17'),
(322, 'App\\Models\\User', 10, 'sso-token', '0c68bba36b5e876e6fb1fcbadd62b12660c8fe751f6cab69f061ab9ef0ea8c5b', '[\"*\"]', '2026-09-15 22:48:28', NULL, '2026-09-15 22:37:15', '2026-09-15 22:48:28'),
(327, 'App\\Models\\User', 10, 'sso-token', '0408e01d0ad69288e7df5bc24123d783ff0d8494e4dd4477b66d5e3ac75cf0d7', '[\"*\"]', '2026-09-16 02:40:40', NULL, '2026-09-16 02:40:38', '2026-09-16 02:40:40'),
(328, 'App\\Models\\User', 10, 'sso-token', '452e1e528e7479d43c21e141ad169a9ddec03d0dcc623356045e8f32be6b209f', '[\"*\"]', '2026-09-16 02:40:55', NULL, '2026-09-16 02:40:54', '2026-09-16 02:40:55'),
(329, 'App\\Models\\User', 11, 'sso-token', 'e903726ab0364027a3ae0faf030a4a09f25f1c47eccd566e45e6a40c5311da4c', '[\"*\"]', '2026-09-16 02:41:31', NULL, '2026-09-16 02:41:30', '2026-09-16 02:41:31'),
(330, 'App\\Models\\User', 11, 'sso-token', 'dd2f17d10458e33e25c965ff2476f18dc2487f547ff7cdec6ce4773585c3fdcd', '[\"*\"]', '2026-09-16 02:41:51', NULL, '2026-09-16 02:41:48', '2026-09-16 02:41:51'),
(345, 'App\\Models\\User', 10, 'sso-token', '72c656a5645e62f2aa5ae2c88f7e414311104e83575ed048f785388a0caa0930', '[\"*\"]', '2026-09-16 10:20:09', NULL, '2026-09-16 10:18:59', '2026-09-16 10:20:09'),
(346, 'App\\Models\\User', 10, 'sso-token', 'ef6cb21379d6aa0e0d316920fc3ba3699f4398a8b9fbb8d5a57a111762d1e4e8', '[\"*\"]', '2026-09-17 00:50:33', NULL, '2026-09-17 00:49:47', '2026-09-17 00:50:33'),
(348, 'App\\Models\\User', 12, 'sso-token', '425e739bce128522e58df3cd02722548e71b4f907482a8f0e32d12ce1f0ce61d', '[\"*\"]', '2026-09-18 04:28:11', NULL, '2026-09-18 04:27:07', '2026-09-18 04:28:11'),
(349, 'App\\Models\\User', 12, 'sso-token', '8bfcff80c75c31d08cbba403a4b5d7a922c35f5cb61b1f268c5bf2747d7f4d43', '[\"*\"]', '2026-09-18 05:42:26', NULL, '2026-09-18 05:41:52', '2026-09-18 05:42:26'),
(350, 'App\\Models\\User', 12, 'sso-token', '5c6fdb89a567927c322598b5cc52566dbcd3e12ceb93a3f4cf1ded1c04b1b500', '[\"*\"]', '2026-09-19 05:27:58', NULL, '2026-09-19 01:16:44', '2026-09-19 05:27:58'),
(351, 'App\\Models\\User', 10, 'sso-token', '61159333bc56ea225d4f246822ac72ad30bf0b4ad0533308190b15715c53c91e', '[\"*\"]', '2026-09-19 01:22:50', NULL, '2026-09-19 01:17:55', '2026-09-19 01:22:50'),
(352, 'App\\Models\\User', 12, 'sso-token', 'ad87d4b917ed1ff387f43283cafec2f1742ccc65d9a6016b47dd95ed69aef337', '[\"*\"]', '2026-09-20 07:25:18', NULL, '2026-09-20 07:14:05', '2026-09-20 07:25:18'),
(353, 'App\\Models\\User', 12, 'sso-token', '2a9855ccb9d35f766538fab638739448385c532370fe9d90ed3ce61e89046f0c', '[\"*\"]', '2026-09-22 07:48:20', NULL, '2026-09-22 07:39:08', '2026-09-22 07:48:20'),
(354, 'App\\Models\\User', 10, 'sso-token', '0b6a5ceae975af1f7d62217de3b5b3d058f2a2d93e33fe6ad95370b0d53100a3', '[\"*\"]', '2026-09-22 08:06:20', NULL, '2026-09-22 08:06:17', '2026-09-22 08:06:20'),
(366, 'App\\Models\\User', 10, 'sso-token', '446117843844048bbfe40da704f7736b7ace0346a519421a68ed0f9396925c45', '[\"*\"]', '2026-09-22 10:34:39', NULL, '2026-09-22 10:33:45', '2026-09-22 10:34:39'),
(367, 'App\\Models\\User', 10, 'sso-token', '90abeb62ddb6528b7ad7c7f8c8bc4e28d6f4df1c5fd41fcaac9942e113d1b812', '[\"*\"]', '2026-09-22 10:35:59', NULL, '2026-09-22 10:34:46', '2026-09-22 10:35:59'),
(368, 'App\\Models\\User', 10, 'sso-token', 'a9c489251938e7b9d6f00d5b4c3408a4f78d60fdd85039bdec2b5595ba7a28e9', '[\"*\"]', '2026-09-26 05:14:15', NULL, '2026-09-26 05:14:14', '2026-09-26 05:14:15'),
(373, 'App\\Models\\User', 10, 'sso-token', '6d911a577325d109a545245e240c58c59ea9ec5de08f2ef8ded9386bdc641432', '[\"*\"]', NULL, NULL, '2026-09-26 06:55:23', '2026-09-26 06:55:23'),
(375, 'App\\Models\\User', 12, 'sso-token', 'adcaa98b1f21244d2cd812611e6fb03ae4045fed60e9dcfb9bfecf08fa78dfe7', '[\"*\"]', '2026-09-26 07:32:49', NULL, '2026-09-26 07:24:47', '2026-09-26 07:32:49'),
(379, 'App\\Models\\User', 12, 'sso-token', 'e90ffa4ac362e3d6852956e909db33c537c8a037eb73a54e0da6fc8651f474a6', '[\"*\"]', '2026-09-26 09:11:56', NULL, '2026-09-26 08:18:44', '2026-09-26 09:11:56'),
(382, 'App\\Models\\User', 10, 'sso-token', '233327b40d65d1d342f5777865a4ecc4bec1efcf07e21f0f8a3e26c971d3a905', '[\"*\"]', '2026-09-26 09:14:22', NULL, '2026-09-26 08:56:39', '2026-09-26 09:14:22'),
(383, 'App\\Models\\User', 12, 'sso-token', '7544aa2e7ff3061efea4ad63aea0e3fbf7355f0a2ec71a16c7ea78dedf1f13ca', '[\"*\"]', '2026-09-26 10:28:47', NULL, '2026-09-26 09:12:13', '2026-09-26 10:28:47'),
(384, 'App\\Models\\User', 10, 'sso-token', '7f9f07db775bfc78687b9cd57a03da3276f2d397468998a60706f5d1610f56d4', '[\"*\"]', '2026-09-26 09:17:46', NULL, '2026-09-26 09:17:29', '2026-09-26 09:17:46'),
(390, 'App\\Models\\User', 13, 'sso-token', 'a7f5a0a82e1bb02907e7c7e408c946dab0534b3bfd8a7502245525c248990d94', '[\"*\"]', '2026-09-26 14:33:24', NULL, '2026-09-26 10:34:42', '2026-09-26 14:33:24'),
(393, 'App\\Models\\User', 10, 'sso-token', '27b5a505bd444f867f40a825fd9baa4813b05c1eb8bdae752743c172215ccfe2', '[\"*\"]', '2026-09-26 14:42:05', NULL, '2026-09-26 14:40:53', '2026-09-26 14:42:05'),
(394, 'App\\Models\\User', 13, 'sso-token', '0901e725da2d738804e838a04373ec6fbd6d431cc8ac8b8f9a1e8c9de306c84a', '[\"*\"]', '2026-09-26 14:42:33', NULL, '2026-09-26 14:41:53', '2026-09-26 14:42:33'),
(395, 'App\\Models\\User', 10, 'sso-token', 'f4d9d1f0dd83703b9858176567176b4e1afd92a0e29b1fb56630fbeefd77ce3d', '[\"*\"]', '2026-09-26 14:58:05', NULL, '2026-09-26 14:58:03', '2026-09-26 14:58:05'),
(398, 'App\\Models\\User', 10, 'sso-token', '930eff9d01b2941b629d0f8c3e3fe60715719732617e4d43f8f2cc9c5861b96e', '[\"*\"]', '2026-09-27 00:10:51', NULL, '2026-09-26 15:02:01', '2026-09-27 00:10:51'),
(399, 'App\\Models\\User', 12, 'sso-token', 'b148637d90d17dda5f4514935a722740411c579d2708b25d3c220f335550398b', '[\"*\"]', '2026-09-26 15:17:55', NULL, '2026-09-26 15:15:24', '2026-09-26 15:17:55'),
(400, 'App\\Models\\User', 10, 'sso-token', '097d4ad71240f5dcae5a44874907b8aa0248e22f64c946cb11f5219489e0d872', '[\"*\"]', '2026-09-27 00:15:30', NULL, '2026-09-27 00:11:16', '2026-09-27 00:15:30');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Super Admin', '-', NULL, NULL),
(2, 'admin', 'Administrator', 'Mangement Aplikasi Helpdesk', NULL, NULL),
(3, 'Pj Unit kerja', 'Penangung Jawab Unit Kerja', '-', NULL, NULL),
(4, 'User Pengguna / Client', 'client', 'Pengguna aplikasi Mahasiswa, Dosen, Staff yang memiliki akun HelpDesk', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('9BzLTBDOk1GgqGYuwbZecgJuYScpK1KdIHzQyD6s', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJadExWdVdldGhXV2xXejBYb0NWZ2lDSFJrZGFNaExUb0FhTmpZNXZ4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1790459859),
('CRhwZ6aIw4wZqNWI9XcYO0JniKpboWcQKfgIQxVp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'eyJfdG9rZW4iOiI4T2hRdWlacHN3VGxUSTQ0NkR2TjBJTnlvSXJqbU9CUmRnVnl1dnd6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9taHMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1789026437),
('dH4cnXRtvBTW0Z8lqEAo9A9PksX9dQ0WlG1QsGWC', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiIwMHJRNWNXdlN4VTVCVmxucWhZN2FIOGU5cUxrM3VMZlhsa1BBU2w3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1790352540),
('EpPOIKh2VnLtJKIbVZ6A1vXJ62YzftpunFiKItUz', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'eyJfdG9rZW4iOiJNaVUwYVVmQk1xdXZyZkJTTnFMM3VCeHVLeUZTS05pa0hWSlVtUEdDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9zc28iLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1788963353),
('gxxX7vKEe5uNt8rzOh9GwqjQkmbM6xCtS1Tn9Xt8', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'eyJfdG9rZW4iOiJ0M1M0Znk5cTNmNmVwTzFCdjZtRDNUTXBaNFhMUHlxRDdlSnpJRWNwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9zc28iLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1789013797),
('kvka2aqIS8Svi8f8KyM3qebZXd0vvNgPA1ILMJEh', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJCbUFwcm1aMVdhU0QxM2h6U0NYRW5GZmFBUlFOYVEyb1VUZGFQN3J1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9zc28iLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1789011846),
('nMpO8uGKV8f3BX1JXw3xXego0DK8FyxccJJKY38Q', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJJc2sxN29GbVpWcUZ2M0tsS2kzdXlHeHhuRW9VU3NUOUR6UWdQazB4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1788619719),
('sl5eay6stJBNmeG5GFR0z5BLYwIu9RsAtmiKJUmK', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', 'eyJfdG9rZW4iOiJ0bHVjNm1VR1pDdEoxMGhMOFdHeFZybWY5dXZOeEI1RUJORmd4dDBSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9zc28iLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1788945931);

-- --------------------------------------------------------

--
-- Table structure for table `statuses`
--

CREATE TABLE `statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statuses`
--

INSERT INTO `statuses` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'New', 'New Ticket', NULL, NULL),
(2, 'In Progress', 'Assigment Ticket To Agent', NULL, NULL),
(3, 'Resolve', 'In Progress (Agent)', NULL, NULL),
(4, 'Close', 'Completed By Agent', NULL, NULL),
(5, 'Reject', 'Reject By Admin/Agent', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nomor_tiket` varchar(30) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` varchar(20) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `comment` text DEFAULT NULL,
  `prioritas` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status_id` bigint(20) UNSIGNED NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `terselesaikan_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `nomor_tiket`, `user_id`, `department_id`, `judul`, `deskripsi`, `comment`, `prioritas`, `status_id`, `lampiran`, `terselesaikan_pada`, `created_at`, `updated_at`, `rating`) VALUES
(16, 'TK-6AA9FF9D22D47', 12, '55202', 'Kendala Elearning', 'Jadwal Algoritma Tidak Muncul', '', 'low', 4, NULL, '2026-09-26 14:38:16', '2026-09-15 19:31:57', '2026-09-26 14:38:16', NULL),
(17, 'TK-6AAA02B2A972B', 12, '55201', 'Kendala Pembayaran', 'Gagal Melakukan Pembayaran Event', 'Proses Pengembalian', 'medium', 4, 'lampiran_tiket/qiFgRwFyMkHPaoqfloEj8quwWU80GKZoi31bEXiB.jpg', '2026-09-26 14:34:18', '2026-09-15 19:45:06', '2026-09-26 14:34:18', NULL),
(25, 'TK-6AB8073890B20', 10, '55201', '1', '1', NULL, 'medium', 3, 'lampiran_tiket/SMTvzlKcd3Y2SWQRipOrT06FPUGd9SyrbnZpqZqN.pdf', NULL, '2026-09-26 10:56:08', '2026-09-26 15:16:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `user_id`, `message`, `created_at`, `updated_at`) VALUES
(16, 16, 12, 'asacxascx', '2026-09-15 19:40:03', '2026-09-15 19:40:03'),
(19, 16, 10, 'halo', '2026-09-16 06:21:44', '2026-09-16 06:21:44'),
(42, 25, 10, 'mmm', '2026-09-26 15:03:13', '2026-09-26 15:03:13');

-- --------------------------------------------------------

--
-- Table structure for table `unanswered_chat_questions`
--

CREATE TABLE `unanswered_chat_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question` text NOT NULL,
  `question_hash` varchar(64) NOT NULL,
  `occurrences` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_asked_at` timestamp NULL DEFAULT NULL,
  `knowledge_base_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tlp` varchar(20) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `role_id`, `department_id`, `name`, `email`, `tlp`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(10, 'admin', 1, '55201', 'super admin', '1@gmail.com', NULL, NULL, '$2y$12$5M4ziB/eq7/oOBU77/xthO8PYoirWw/vrDn/JRI8U6HAHQU7UZfAa', NULL, NULL, '2026-09-15 08:26:06'),
(11, 'admin2', 1, '55201', 'admin2', 'admin2@gmail.com', NULL, NULL, '$2y$12$Yaj1BXIHFCocopWVCLcZNero03AzEkNJuuIkoVqWd8TbFXCUlkWdS', NULL, '2026-09-15 08:24:27', '2026-09-15 08:24:27'),
(12, 'userti', 4, '55201', 'Ari Syaripudin', 'userti@gmail.com', '081293812467', NULL, '$2y$12$h4.LdBq3tnQ6fmGj31fYr.1q2vyJO7syaE.ERHVhmbgoLUoFIdDhC', NULL, '2026-09-15 08:29:23', '2026-09-15 21:12:38'),
(13, 'ti', 3, '55201', 'ti', 'ti@gmail.com', '12345678', NULL, '$2y$12$IYP8nIBCnca62dPt.C4geeZwNJgWrjK7la8Auy9IcQOB5OMu4DC7a', NULL, '2026-09-26 06:47:26', '2026-09-26 06:47:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`kode`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `knowledge_bases`
--
ALTER TABLE `knowledge_bases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `statuses_nama_unique` (`name`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stat` (`status_id`),
  ADD KEY `user` (`user_id`),
  ADD KEY `dept` (`department_id`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_messages_ticket_id_foreign` (`ticket_id`),
  ADD KEY `ticket_messages_user_id_foreign` (`user_id`);

--
-- Indexes for table `unanswered_chat_questions`
--
ALTER TABLE `unanswered_chat_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unanswered_chat_questions_question_hash_unique` (`question_hash`),
  ADD KEY `unanswered_chat_questions_knowledge_base_id_foreign` (`knowledge_base_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_role` (`role_id`),
  ADD KEY `fk_dept` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_bases`
--
ALTER TABLE `knowledge_bases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=401;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `unanswered_chat_questions`
--
ALTER TABLE `unanswered_chat_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`kode`),
  ADD CONSTRAINT `stat` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`),
  ADD CONSTRAINT `user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unanswered_chat_questions`
--
ALTER TABLE `unanswered_chat_questions`
  ADD CONSTRAINT `unanswered_chat_questions_knowledge_base_id_foreign` FOREIGN KEY (`knowledge_base_id`) REFERENCES `knowledge_bases` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
