-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 10, 2026 at 03:26 PM
-- Server version: 8.4.7-7
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `reptrack`
--
CREATE DATABASE IF NOT EXISTS `reptrack` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `reptrack`;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` bigint UNSIGNED NOT NULL,
  `governorate_id` bigint UNSIGNED DEFAULT NULL,
  `name_fr` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ar` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `governorate_id`, `name_fr`, `name_ar`, `postal_code`, `latitude`, `longitude`) VALUES
(1, 1, 'ARIANA VILLE', 'أريانة المدينة', '2058', 36.8664740, 10.1647260),
(2, 1, 'SIDI THABET', 'سيدي ثابت', '2020', 36.8986140, 10.0306320),
(3, 1, 'LA SOUKRA', 'سكرة', '2036', 36.8836180, 10.2408740),
(4, 1, 'KALAAT LANDLOUS', 'قلعة الأندلس', '2061', 37.0666670, 10.1833330),
(5, 1, 'RAOUED', 'رواد', '2083', 36.9319440, 10.1602780),
(6, 1, 'MNIHLA', 'المنيهلة', '2094', 36.8663310, 10.0896340),
(7, 1, 'ETTADHAMEN', 'التضامن', '2041', 36.8398210, 10.0992050),
(8, 2, 'TESTOUR', 'تستور', '9014', 36.5523050, 9.4443030),
(9, 2, 'TEBOURSOUK', 'تبرسق', '9032', 36.4580380, 9.2490010),
(10, 2, 'BEJA NORD', 'باجة الشمالية', '9000', 36.7288260, 9.1832180),
(11, 2, 'MEJEZ EL BAB', 'مجاز الباب', '9034', 36.6494440, 9.6091670),
(12, 2, 'NEFZA', 'نفزة', '9010', 37.0744440, 9.0858330),
(13, 2, 'BEJA SUD', 'باجة الجنوبية', '9021', 36.7288260, 9.1832180),
(14, 2, 'THIBAR', 'تيبار', '9022', 36.6119440, 9.0597220),
(15, 2, 'AMDOUN', 'عمدون', '9030', 36.8391670, 9.0811110),
(16, 2, 'GOUBELLAT', 'قبلاط', '9080', 36.5341670, 9.6000000),
(17, 3, 'FOUCHANA', 'فوشانة', '2082', 36.7038890, 10.1550000),
(18, 3, 'HAMMAM LIF', 'حمام الأنف', '2050', 36.7277780, 10.3361110),
(19, 3, 'EL MOUROUJ', 'المروج', '2074', 36.7398890, 10.2050000),
(20, 3, 'BOU MHEL EL BASSATINE', 'بومهل البساتين', '2097', 36.7294440, 10.2800000),
(21, 3, 'RADES', 'رادس', '2098', 36.7666670, 10.2833330),
(22, 3, 'MOHAMADIA', 'المحمدية', '1145', 36.6894440, 10.1313890),
(23, 3, 'MEGRINE', 'مقرين', '2033', 36.7811110, 10.2383330),
(24, 3, 'NOUVELLE MEDINA', 'المدينة الجديدة', '2063', 36.7538890, 10.2322220),
(25, 3, 'HAMMAM CHATT', 'حمام الشط', '1164', 36.7000000, 10.3666670),
(26, 3, 'MORNAG', 'مرناق', '2064', 36.6333330, 10.2500000),
(27, 3, 'EZZAHRA', 'الزهراء', '2034', 36.7433330, 10.3083330),
(28, 3, 'BEN AROUS', 'بن عروس', '2043', 36.7483330, 10.2225000),
(29, 4, 'MENZEL JEMIL', 'منزل جميل', '7035', 37.2333330, 9.9166670),
(30, 4, 'BIZERTE SUD', 'بنزرت الجنوبية', '7071', 37.2744440, 9.8738890),
(31, 4, 'SEJNANE', 'سجنان', '7010', 37.1538890, 9.2388890),
(32, 4, 'GHAR EL MELH', 'غار الملح', '7024', 37.1666670, 10.1930560),
(33, 4, 'MENZEL BOURGUIBA', 'منزل بورقيبة', '7072', 37.1500000, 9.7833330),
(34, 4, 'RAS JEBEL', 'رأس الجبل', '7025', 37.2147220, 10.1213890),
(35, 4, 'GHEZALA', 'غزالة', '7040', 37.1166670, 9.5333330),
(36, 4, 'JOUMINE', 'جومين', '7012', 36.9500000, 9.4000000),
(37, 4, 'UTIQUE', 'أوتيك', '7013', 37.0525000, 10.0588890),
(38, 4, 'BIZERTE NORD', 'بنزرت الشمالية', '7029', 37.2744440, 9.8738890),
(39, 4, 'EL ALIA', 'العالية', '7081', 37.1691670, 10.0450000),
(40, 4, 'MATEUR', 'ماطر', '7030', 37.0402780, 9.6655560),
(41, 4, 'JARZOUNA', 'جرزونة', '7021', 37.2588890, 9.8822220),
(42, 4, 'TINJA', 'تينجة', '7032', 37.1616670, 9.7594440),
(43, 5, 'GABES SUD', 'قابس الجنوبية', '6012', 33.8814530, 10.0981950),
(44, 5, 'MATMATA', 'مطماطة', '6034', 33.5427780, 9.9747220),
(45, 5, 'MARETH', 'مارث', '6080', 33.6277780, 10.2958330),
(46, 5, 'EL HAMMA', 'الحامة', '6013', 33.8888890, 9.7944440),
(47, 5, 'NOUVELLE MATMATA', 'مطماطة الجديدة', '6044', 33.7022220, 10.0252780),
(48, 5, 'GABES MEDINA', 'قابس المدينة', '6040', 33.8863000, 10.1128000),
(49, 5, 'GABES OUEST', 'قابس الغربية', '6041', 33.8814530, 10.0981950),
(50, 5, 'EL METOUIA', 'المطوية', '6052', 33.9611110, 10.0055560),
(51, 5, 'GHANNOUCHE', 'غنوش', '6021', 33.9333330, 10.0666670),
(52, 5, 'MENZEL HABIB', 'منزل الحبيب', '6030', 34.1258330, 9.7033330),
(53, 6, 'BELKHIR', 'بلخير', '2135', 34.4666670, 9.0666670),
(54, 6, 'GAFSA NORD', 'قفصة الشمالية', '2196', 34.4250000, 8.7841670),
(55, 6, 'SNED', 'السند', '2116', 34.4722220, 9.2111110),
(56, 6, 'REDEYEF', 'الرديف', '2140', 34.3833330, 8.1500000),
(57, 6, 'GAFSA SUD', 'قفصة الجنوبية', '2100', 34.4250000, 8.7841670),
(58, 6, 'EL GUETTAR', 'القطار', '2145', 34.3283330, 8.9519440),
(59, 6, 'EL KSAR', 'القصر', '2151', 34.4000000, 8.8166670),
(60, 6, 'MOULARES', 'أم العرائس', '2161', 34.4944440, 8.2519440),
(61, 6, 'EL MDHILLA', 'المظيلة', '2170', 34.3233330, 8.6025000),
(62, 6, 'METLAOUI', 'المتلوي', '2130', 34.3258330, 8.4013890),
(63, 6, 'SIDI AICH', 'سيدي عيش', '2131', 34.6000000, 8.8833330),
(64, 7, 'BALTA BOU AOUENE', 'بلطة بوعوان', '8116', 36.4500000, 8.9666670),
(65, 7, 'FERNANA', 'فرنانة', '8142', 36.6525000, 8.6930560),
(66, 7, 'JENDOUBA NORD', 'جندوبة الشمالية', '8189', 36.5010000, 8.7800000),
(67, 7, 'AIN DRAHAM', 'عين دراهم', '8121', 36.7775000, 8.6919440),
(68, 7, 'TABARKA', 'طبرقة', '8192', 36.9544440, 8.7580560),
(69, 7, 'JENDOUBA', 'جندوبة', '8122', 36.5010000, 8.7800000),
(70, 7, 'BOU SALEM', 'بوسالم', '8143', 36.6116670, 8.9688890),
(71, 7, 'OUED MLIZ', 'وادي مليز', '8193', 36.4666670, 8.5500000),
(72, 7, 'GHARDIMAOU', 'غار الدماء', '8160', 36.4794440, 8.4397220),
(73, 8, 'CHEBIKA', 'الشبيكة', '3121', 35.6833330, 9.7500000),
(74, 8, 'EL ALA', 'العلا', '3154', 35.6083330, 9.5500000),
(75, 8, 'OUESLATIA', 'الوسلاتية', '3124', 35.8500000, 9.6000000),
(76, 8, 'HAJEB EL AYOUN', 'حاجب العيون', '3160', 35.3833330, 9.5500000),
(77, 8, 'SBIKHA', 'السبيخة', '3125', 35.9333330, 10.0000000),
(78, 8, 'BOU HAJLA', 'بوحجلة', '3126', 35.2500000, 10.0166670),
(79, 8, 'KAIROUAN NORD', 'القيروان الشمالية', '3129', 35.6780560, 10.0963060),
(80, 8, 'HAFFOUZ', 'حفوز', '3130', 35.6333330, 9.6666670),
(81, 8, 'KAIROUAN SUD', 'القيروان الجنوبية', '3131', 35.6780560, 10.0963060),
(82, 8, 'NASRALLAH', 'نصر الله', '3170', 35.0500000, 9.7333330),
(83, 8, 'CHERARDA', 'الشراردة', '3145', 35.4522220, 10.2300000),
(84, 9, 'SBEITLA', 'سبيطلة', '1250', 35.2333330, 9.1333330),
(85, 9, 'FOUSSANA', 'فوسانة', '1220', 35.1000000, 8.6666670),
(86, 9, 'KASSERINE NORD', 'القصرين الشمالية', '1253', 35.1676000, 8.8302000),
(87, 9, 'HAIDRA', 'حيدرة', '1221', 35.5625000, 8.4944440),
(88, 9, 'THALA', 'تالة', '1210', 35.5750000, 8.6722220),
(89, 9, 'SBIBA', 'سبيبة', '1270', 35.5500000, 9.0666670),
(90, 9, 'FERIANA', 'فريانة', '1240', 34.9500000, 8.5833330),
(91, 9, 'MEJEL BEL ABBES', 'ماجل بلعباس', '1226', 34.8000000, 8.8333330),
(92, 9, 'KASSERINE SUD', 'القصرين الجنوبية', '1233', 35.1676000, 8.8302000),
(93, 9, 'EL AYOUN', 'العيون', '1234', 35.3833330, 8.7000000),
(94, 9, 'EZZOUHOUR  (KASSERINE)', 'الزهور (القصرين)', '1279', 35.1833000, 8.8000000),
(95, 9, 'JEDILIANE', 'جدليان', '1280', 35.6166670, 9.1833330),
(96, 9, 'HASSI EL FRID', 'حاسي الفريد', '1241', 34.9333330, 9.0000000),
(97, 10, 'SOUK EL AHAD', 'سوق الأحد', '4223', 33.7000000, 8.9500000),
(98, 10, 'KEBILI SUD', 'قبلي الجنوبية', '4224', 33.7051110, 8.8723060),
(99, 10, 'KEBILI NORD', 'قبلي الشمالية', '4232', 33.7051110, 8.8723060),
(100, 10, 'DOUZ', 'دوز', '4234', 33.4588890, 9.0255560),
(101, 10, 'EL FAOUAR', 'الفوار', '4264', 33.3500000, 8.6166670),
(102, 11, 'TAJEROUINE', 'تاجروين', '7150', 35.8833330, 8.6166670),
(103, 11, 'DAHMANI', 'الدهماني', '7170', 35.9500000, 8.8166670),
(104, 11, 'LE KEF EST', 'الكاف الشرقية', '7100', 36.1802780, 8.7111110),
(105, 11, 'SAKIET SIDI YOUSSEF', 'ساقية سيدي يوسف', '7120', 36.3500000, 8.3500000),
(106, 11, 'LE SERS', 'السرس', '7180', 36.0833330, 9.0333330),
(107, 11, 'NEBEUR', 'نبر', '7110', 36.3666670, 8.8166670),
(108, 11, 'TOUIREF', 'الطويرف', '7112', 36.2833330, 8.5500000),
(109, 11, 'EL KSOUR', 'القصور', '7160', 35.8000000, 8.8666670),
(110, 11, 'KALAA EL KHASBA', 'القلعة الخصباء', '7123', 35.6333330, 8.4500000),
(111, 11, 'KALAAT SINANE', 'قلعة سنان', '7130', 35.9500000, 8.4666670),
(112, 11, 'JERISSA', 'الجريصة', '7114', 35.8666670, 8.6333330),
(113, 11, 'LE KEF OUEST', 'الكاف الغربية', '7117', 36.1802780, 8.7111110),
(114, 12, 'MAHDIA', 'المهدية', '5111', 35.5047220, 11.0622220),
(115, 12, 'CHORBANE', 'شربان', '5130', 35.2666670, 10.5166670),
(116, 12, 'EL JEM', 'الجم', '5160', 35.2963890, 10.7111110),
(117, 12, 'LA CHEBBA', 'الشابة', '5170', 35.2333330, 11.1166670),
(118, 12, 'BOU MERDES', 'بومرداس', '5112', 35.5500000, 10.7666670),
(119, 12, 'KSOUR ESSAF', 'قصور الساف', '5180', 35.4266670, 10.9900000),
(120, 12, 'SIDI ALOUENE', 'سيدي علوان', '5132', 35.3500000, 10.8166670),
(121, 12, 'HBIRA', 'هبيرة', '5113', 35.1166670, 10.4000000),
(122, 12, 'MELLOULECH', 'ملولش', '5114', 35.3166670, 11.0166670),
(123, 12, 'SOUASSI', 'السواسي', '5134', 35.3500000, 10.4833330),
(124, 12, 'OULED CHAMAKH', 'أولاد الشامخ', '5120', 35.4000000, 10.3000000),
(125, 14, 'HOUMET ESSOUK', 'حومة السوق', '4180', 33.8750000, 10.8583330),
(126, 14, 'BENI KHEDACHE', 'بني خداش', '4110', 33.2500000, 10.2000000),
(127, 14, 'AJIM', 'أجيم', '4150', 33.7205560, 10.7519440),
(128, 14, 'BEN GUERDANE', 'بنقردان', '4153', 33.1333330, 11.2166670),
(129, 14, 'ZARZIS', 'جرجيس', '4154', 33.5038890, 11.1122220),
(130, 14, 'MEDENINE NORD', 'مدنين الشمالية', '4111', 33.3550000, 10.5052780),
(131, 14, 'MIDOUN', 'ميدون', '4113', 33.8047220, 10.9647220),
(132, 14, 'SIDI MAKHLOUF', 'سيدي مخلوف', '4181', 33.5666670, 10.4500000),
(133, 14, 'MEDENINE SUD', 'مدنين الجنوبية', '4127', 33.3550000, 10.5052780),
(134, 15, 'MONASTIR', 'المنستير', '5060', 35.7642980, 10.8090980),
(135, 15, 'SAHLINE', 'الساحلين', '5012', 35.7500000, 10.7333330),
(136, 15, 'KSIBET EL MEDIOUNI', 'قصيبة المديوني', '5031', 35.6666670, 10.8000000),
(137, 15, 'JEMMAL', 'جمال', '5013', 35.6250000, 10.7541670),
(138, 15, 'BENI HASSEN', 'بني حسان', '5014', 35.5666670, 10.7333330),
(139, 15, 'SAYADA LAMTA BOU HAJAR', 'صيادة لمطة بوحجر', '5015', 35.6666670, 10.8833330),
(140, 15, 'TEBOULBA', 'طبلبة', '5066', 35.6405560, 10.9613890),
(141, 15, 'KSAR HELAL', 'قصر هلال', '5016', 35.6441670, 10.8927780),
(142, 15, 'BEMBLA', 'بنبلة', '5032', 35.7000000, 10.7833330),
(143, 15, 'ZERAMDINE', 'زرمدين', '5033', 35.5833330, 10.7000000),
(144, 15, 'MOKNINE', 'المكنين', '5034', 35.6305560, 10.9000000),
(145, 15, 'OUERDANINE', 'الوردانين', '5041', 35.7833330, 10.6833330),
(146, 15, 'BEKALTA', 'البقالطة', '5090', 35.6166670, 11.0333330),
(147, 16, 'BENI KHIAR', 'بني خيار', '8023', 36.4666670, 10.7833330),
(148, 16, 'TAKELSA', 'تاكلسة', '8031', 36.7833330, 10.6333330),
(149, 16, 'EL MIDA', 'الميدة', '8044', 36.7333330, 10.9166670),
(150, 16, 'MENZEL BOUZELFA', 'منزل بوزلفة', '8010', 36.6833330, 10.5833330),
(151, 16, 'KELIBIA', 'قليبية', '8090', 36.8461110, 11.0975000),
(152, 16, 'HAMMAMET', 'الحمامات', '8032', 36.4000000, 10.6166670),
(153, 16, 'BOU ARGOUB', 'بوعرقوب', '8061', 36.5500000, 10.5500000),
(154, 16, 'KORBA', 'قربة', '8033', 36.5752780, 10.8622220),
(155, 16, 'MENZEL TEMIME', 'منزل تميم', '8034', 36.7833330, 10.9833330),
(156, 16, 'NABEUL', 'نابل', '8062', 36.4560650, 10.7346160),
(157, 16, 'EL HAOUARIA', 'الهوارية', '8036', 37.0500000, 11.0166670),
(158, 16, 'HAMMAM EL GHEZAZ', 'حمام الأغزاز', '8025', 36.9666670, 11.1166670),
(159, 16, 'SOLIMAN', 'سليمان', '8063', 36.7000000, 10.4833330),
(160, 16, 'GROMBALIA', 'قرمبالية', '8092', 36.6000000, 10.5000000),
(161, 16, 'DAR CHAABANE ELFEHRI', 'دار شعبان الفهري', '8011', 36.4733330, 10.7558330),
(162, 16, 'BENI KHALLED', 'بني خلاد', '8099', 36.6500000, 10.6000000),
(163, 17, 'AGAREB', 'عقارب', '3030', 34.7333330, 10.5166670),
(164, 17, 'EL HENCHA', 'الحنشة', '3043', 35.2333330, 10.6000000),
(165, 17, 'SFAX EST', 'صفاقس الشرقية', '3064', 34.7400000, 10.7600000),
(166, 17, 'SFAX SUD', 'صفاقس الجنوبية', '3083', 34.7400000, 10.7600000),
(167, 17, 'MAHRAS', 'المحرس', '3044', 34.5277780, 10.5055560),
(168, 17, 'SFAX VILLE', 'صفاقس المدينة', '3065', 34.7375000, 10.7577780),
(169, 17, 'EL AMRA', 'العامرة', '3066', 34.9000000, 10.6166670),
(170, 17, 'BIR ALI BEN KHELIFA', 'بئر علي بن خليفة', '3085', 34.8333330, 10.0666670),
(171, 17, 'KERKENAH', 'قرقنة', '3045', 34.7208330, 11.1500000),
(172, 17, 'SAKIET EDDAIER', 'ساقية الداير', '3011', 34.8166670, 10.7666670),
(173, 17, 'JEBENIANA', 'جبنيانة', '3086', 35.0333330, 10.9000000),
(174, 17, 'SAKIET EZZIT', 'ساقية الزيت', '3091', 34.7944440, 10.7433330),
(175, 17, 'MENZEL CHAKER', 'منزل شاكر', '3092', 34.9666670, 10.3666670),
(176, 17, 'ESSKHIRA', 'الصخيرة', '3050', 34.2916670, 10.0722220),
(177, 17, 'GHRAIBA', 'الغريبة', '3034', 34.5500000, 10.1666670),
(178, 18, 'MENZEL BOUZAIENE', 'منزل بوزيان', '9114', 34.7833330, 9.2500000),
(179, 18, 'SIDI BOUZID OUEST', 'سيدي بوزيد الغربية', '9131', 35.0372220, 9.4847220),
(180, 18, 'BEN OUN', 'بن عون', '9169', 34.8666670, 9.0666670),
(181, 18, 'SIDI BOUZID EST', 'سيدي بوزيد الشرقية', '9100', 35.0372220, 9.4847220),
(182, 18, 'OULED HAFFOUZ', 'أولاد حفوز', '9180', 35.1833330, 9.2000000),
(183, 18, 'REGUEB', 'الرقاب', '9115', 34.8500000, 9.7666670),
(184, 18, 'MAKNASSY', 'المكناسي', '9140', 34.6000000, 9.6000000),
(185, 18, 'JILMA', 'جلمة', '9110', 35.2833330, 9.4166670),
(186, 18, 'SOUK JEDID', 'السوق الجديد', '9121', 35.1000000, 9.3166670),
(187, 18, 'MEZZOUNA', 'المزونة', '9150', 34.5166670, 9.8333330),
(188, 18, 'BIR EL HAFFEY', 'بئر الحفي', '9113', 34.9333330, 9.2000000),
(189, 18, 'CEBBALA', 'السبالة', '9122', 35.1333330, 9.0833330),
(190, 19, 'MAKTHAR', 'مكثر', '6140', 35.8500000, 9.2000000),
(191, 19, 'BOU ARADA', 'بوعرادة', '6180', 36.3500000, 9.6166670),
(192, 19, 'SIDI BOU ROUIS', 'سيدي بورويس', '6113', 36.1166670, 9.3333330),
(193, 19, 'KESRA', 'كسرى', '6114', 35.8166670, 9.3666670),
(194, 19, 'BARGOU', 'برقو', '6115', 36.0833330, 9.6000000),
(195, 19, 'EL AROUSSA', 'العروسة', '6116', 36.3500000, 9.3833330),
(196, 19, 'LE KRIB', 'الكريب', '6120', 36.2833330, 9.1833330),
(197, 19, 'SILIANA NORD', 'سليانة الشمالية', '6100', 36.0848900, 9.3700000),
(198, 19, 'SILIANA SUD', 'سليانة الجنوبية', '6143', 36.0848900, 9.3700000),
(199, 19, 'ROHIA', 'الروحية', '6150', 35.6500000, 9.0000000),
(200, 19, 'GAAFOUR', 'قعفور', '6121', 36.2833330, 9.4166670),
(201, 20, 'SIDI EL HENI', 'سيدي الهاني', '4026', 35.6666670, 10.3000000),
(202, 20, 'SOUSSE JAOUHARA', 'سوسة جوهرة', '4054', 35.8253540, 10.6079950),
(203, 20, 'BOU FICHA', 'بوفيشة', '4010', 36.2666670, 10.4000000),
(204, 20, 'SOUSSE VILLE', 'سوسة المدينة', '4059', 35.8288280, 10.6400360),
(205, 20, 'ENFIDHA', 'النفيضة', '4030', 36.1333330, 10.3833330),
(206, 20, 'KALAA EL KEBIRA', 'القلعة الكبرى', '4060', 35.8666670, 10.5333330),
(207, 20, 'HAMMAM SOUSSE', 'حمام سوسة', '4011', 35.8611110, 10.5972220),
(208, 20, 'HERGLA', 'هرقلة', '4012', 36.0333330, 10.5000000),
(209, 20, 'MSAKEN', 'مساكن', '4013', 35.7294440, 10.5800000),
(210, 20, 'SOUSSE RIADH', 'سوسة الرياض', '4081', 35.8094440, 10.5916670),
(211, 20, 'KONDAR', 'كندار', '4020', 35.9333330, 10.3166670),
(212, 20, 'SIDI BOU ALI', 'سيدي بوعلي', '4040', 35.9666670, 10.4666670),
(213, 20, 'KALAA ESSGHIRA', 'القلعة الصغرى', '4021', 35.8333330, 10.5666670),
(214, 20, 'AKOUDA', 'أكودة', '4022', 35.8711110, 10.5638890),
(215, 21, 'TATAOUINE SUD', 'تطاوين الجنوبية', '3200', 32.9297220, 10.4513890),
(216, 21, 'SMAR', 'الصمار', '3223', 33.1166670, 10.7833330),
(217, 21, 'BIR LAHMAR', 'بئر الأحمر', '3212', 33.2000000, 10.5833330),
(218, 21, 'GHOMRASSEN', 'غمراسن', '3224', 33.0500000, 10.3333330),
(219, 21, 'TATAOUINE NORD', 'تطاوين الشمالية', '3233', 32.9297220, 10.4513890),
(220, 21, 'REMADA', 'رمادة', '3240', 32.3000000, 10.3833330),
(221, 21, 'DHEHIBA', 'الذهيبة', '3253', 32.0000000, 10.7000000),
(222, 22, 'DEGUECHE', 'دقاش', '2261', 33.9666670, 8.2166670),
(223, 22, 'TOZEUR', 'توزر', '2200', 33.9197220, 8.1336110),
(224, 22, 'TAMEGHZA', 'تمغزة', '2211', 34.3833330, 7.9333330),
(225, 22, 'HEZOUA', 'حزوة', '2223', 33.7500000, 7.8333330),
(226, 22, 'NEFTA', 'نفطة', '2240', 33.8730560, 7.8833330),
(227, 23, 'JEBEL JELLOUD', 'جبل الجلود', '1046', 36.7750000, 10.1958330),
(228, 23, 'CARTHAGE', 'قرطاج', '2016', 36.8536110, 10.3322220),
(229, 23, 'LA MARSA', 'المرسى', '2076', 36.8776000, 10.3278000),
(230, 23, 'BAB BHAR', 'باب بحر', '1000', 36.7983330, 10.1800000),
(231, 23, 'LA GOULETTE', 'حلق الوادي', '2060', 36.8188890, 10.3000000),
(232, 23, 'LE BARDO', 'باردو', '2017', 36.8092780, 10.1395000),
(233, 23, 'LA MEDINA', 'تونس المدينة', '1000', 36.8000000, 10.1716670),
(234, 23, 'EL MENZAH', 'المنزه', '2092', 36.8451940, 10.1766940),
(235, 23, 'EL OMRANE SUPERIEUR', 'العمران الأعلى', '1064', 36.8225000, 10.1622220),
(236, 23, 'CITE EL KHADRA', 'حي الخضراء', '1002', 36.8343000, 10.1990000),
(237, 23, 'EL HRAIRIA', 'الحرايرية', '2051', 36.7752780, 10.1027780),
(238, 23, 'EL KABBARIA', 'الكبارية', '1074', 36.7666670, 10.1750000),
(239, 23, 'BAB SOUIKA', 'باب سويقة', '1075', 36.8075000, 10.1669440),
(240, 23, 'EL OMRANE', 'العمران', '1005', 36.8200000, 10.1550000),
(241, 23, 'EZZOUHOUR  (TUNIS)', 'الزهور (تونس)', '2052', 36.7885000, 10.1290000),
(242, 23, 'SIDI EL BECHIR', 'سيدي البشير', '1089', 36.7833330, 10.1900000),
(243, 23, 'SIDI HASSINE', 'سيدي حسين', '1095', 36.7944440, 10.0833330),
(244, 23, 'EL KRAM', 'الكرم', '2089', 36.8333330, 10.3166670),
(245, 23, 'ESSIJOUMI', 'السيجومي', '2072', 36.7833330, 10.1333330),
(246, 23, 'ETTAHRIR', 'التحرير', '2042', 36.8261110, 10.1427780),
(247, 23, 'EL OUERDIA', 'الوردية', '1009', 36.7725000, 10.1850000),
(248, 24, 'ZAGHOUAN', 'زغوان', '1100', 36.4000000, 10.1500000),
(249, 24, 'ENNADHOUR', 'الناظور', '1160', 36.2166670, 10.0666670),
(250, 24, 'EL FAHS', 'الفحص', '1140', 36.3761110, 9.9041670),
(251, 24, 'BIR MCHERGA', 'بئر مشارقة', '1111', 36.5166670, 10.0166670),
(252, 24, 'HAMMAM ZRIBA', 'حمام الزريبة', '1112', 36.3000000, 10.2166670),
(253, 24, 'SAOUEF', 'صواف', '1115', 36.2666670, 9.8166670);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('doctor','pharmacy','parapharmacie','clinic','hospital') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialty` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `establishment` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `governorate_id` bigint UNSIGNED DEFAULT NULL,
  `city_id` bigint UNSIGNED DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('chain','independent','group','hospital_public','clinic_private') COLLATE utf8mb4_unicode_ci DEFAULT 'independent',
  `potential` enum('A','B','C') COLLATE utf8mb4_unicode_ci DEFAULT 'B',
  `client_type` enum('local','tourist','specialized','mixed') COLLATE utf8mb4_unicode_ci DEFAULT 'local',
  `collaboration_history` enum('new','occasional','regular','key_account') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `plv_present` tinyint(1) DEFAULT '0',
  `team_engagement` enum('low','medium','high') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `specific_needs` text COLLATE utf8mb4_unicode_ci,
  `visit_frequency_days` int DEFAULT '30',
  `assigned_rep_id` bigint UNSIGNED DEFAULT NULL,
  `added_by` bigint UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `type`, `name`, `specialty`, `establishment`, `governorate_id`, `city_id`, `address`, `latitude`, `longitude`, `phone`, `email`, `contact_person`, `status`, `potential`, `client_type`, `collaboration_history`, `plv_present`, `team_engagement`, `specific_needs`, `visit_frequency_days`, `assigned_rep_id`, `added_by`, `notes`, `active`, `created_at`) VALUES
(1, 'parapharmacie', 'Anais parapharmacie', NULL, NULL, 1, 1, 'Rue Martyr Mohamed Brahmi', NULL, NULL, '70 935 808', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:12:15'),
(2, 'parapharmacie', 'Paraphamacie Pharma Shop', NULL, NULL, 1, 1, 'El Menzah 6', NULL, NULL, '70 816 405', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:13:29'),
(3, 'parapharmacie', 'ParaShop', NULL, NULL, 1, 1, '82 Rue Mouawiya Ibn Abi Sofiene', NULL, NULL, '20 062 374', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:14:09'),
(4, 'parapharmacie', 'Parapharmacie Essentiel Beauté', NULL, NULL, 1, 1, 'Rue de Sfax', NULL, NULL, '98 517 065', NULL, NULL, 'independent', 'C', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:16:06'),
(5, 'parapharmacie', 'Parapharmacie Para Pro', NULL, NULL, 1, 1, '47 Rue Abderrahmen Ibn Aouf، UV4 Menzeh 6', NULL, NULL, '58 887 824', NULL, NULL, 'independent', 'C', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:16:47'),
(6, 'parapharmacie', 'Eden Pharma', NULL, NULL, 1, 1, 'Immeuble La perle de l, Magasin N°2, 11 Av. Taieb Mhiri', NULL, NULL, '28 372 827', NULL, 'Oumaima', 'independent', 'B', 'local', 'regular', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:17:31'),
(7, 'parapharmacie', 'PARA TOWN parapharmacie', NULL, NULL, 1, 1, '25, 1 Av. de l’Ère Nouvelle', NULL, NULL, '70 819 122', NULL, NULL, 'independent', 'C', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:18:10'),
(8, 'parapharmacie', 'Parapharmacie el farabi', NULL, NULL, 1, 1, '3, Rue Cheikh Mohamed Zaghouani à coté de clinique el farabi Menzah 6', NULL, NULL, '70 318 060', NULL, NULL, 'independent', 'C', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:19:21'),
(9, 'parapharmacie', 'ML Para', NULL, NULL, 1, 1, '28 Ave d\'Afrique', NULL, NULL, '46 999 992', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:20:27'),
(10, 'parapharmacie', 'Paraclic', NULL, NULL, 1, 1, 'Résidence Chiraz avenue Taher, Rue Tahar Sfar', NULL, NULL, '52 444 333', NULL, NULL, 'independent', 'C', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:21:00'),
(11, 'parapharmacie', 'Parapharmacie Kamoun Medical Center', NULL, NULL, 1, 1, 'Municipalité Riadh Ennasr, Kamoun Medical Center, Im \"Yesmine\", Magasin 3 Bis Ennasr 2', NULL, NULL, '29 767 848', NULL, NULL, 'independent', 'C', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:21:41'),
(12, 'parapharmacie', 'Parapharmacie Côté Para', NULL, NULL, 1, 1, 'V6JG+W2R, Rue Chohrour', NULL, NULL, '90 301 300', NULL, NULL, 'independent', 'C', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:22:17'),
(13, 'parapharmacie', 'Parapharmacie cosmetica', NULL, NULL, 1, 1, 'V579+X4J Residence osalis garden, Av. Hédi Nouira', NULL, NULL, '71 816 517', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:23:01'),
(14, 'parapharmacie', 'Le temps des para', NULL, NULL, 1, 1, 'V42Q+8XF', NULL, NULL, '25 260 024', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, NULL, 1, NULL, 1, '2026-04-09 13:23:36'),
(15, 'parapharmacie', 'Paraexpert.tn', NULL, NULL, 1, 1, 'Résidence la Princesse, 56 Avenue Des Jasmins', NULL, NULL, '29 003 469', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:24:14'),
(16, 'parapharmacie', 'ParaPharmacie Plus Sfax Lafrane', NULL, NULL, 17, 168, 'QP5M+564 face urgence clinique, Rte Lafrane', NULL, NULL, '53 125 398', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:25:05'),
(17, 'parapharmacie', 'Parapharmacie Du Bonheur', NULL, NULL, 17, 168, 'Route de Teniour Km 0.5 , en face du CNAM', NULL, NULL, '29 702 050', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:25:45'),
(18, 'parapharmacie', 'Parapharmacie Royale', NULL, NULL, 17, 168, 'Les 100 mètres En face du Théâtre Municipal, 28 Av. Hedi Chaker', NULL, NULL, '44 432 888', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:26:18'),
(19, 'parapharmacie', 'PARASTORE AIN', NULL, NULL, 17, 168, 'Résidence Khadija, Km 2 Rte El Ain', NULL, NULL, '98 763 082', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:26:48'),
(20, 'parapharmacie', 'Parapharmacie plus sfax Teniour', NULL, NULL, 17, 168, 'QQ93+F92, Rte Teniour', NULL, NULL, '50 125 116', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:27:36'),
(21, 'parapharmacie', 'PARA PARAXI', NULL, NULL, 17, 168, '79 Av. des Martyrs', NULL, NULL, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:28:17'),
(22, 'parapharmacie', 'PARAPHARMACIE PLUS ELAIN', NULL, NULL, 17, 168, 'RTE EL AIN KM2, SFAX, DEVANT PLYCLINIQUE', NULL, NULL, '53 125 979', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:28:54'),
(23, 'parapharmacie', 'Paranaturalia', NULL, NULL, 17, 168, 'Résidence Aubergine, Route l\'afran, km4 rue Sidi Jilen', NULL, NULL, '26 400 759', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:29:22'),
(24, 'parapharmacie', 'Parapharmacie MEDI SPACE', NULL, NULL, 17, 168, 'Route mahdia km 2.5', NULL, NULL, '23 048 751', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:29:49'),
(25, 'parapharmacie', 'Paraphamacie Soukra Centre', NULL, NULL, 17, 168, 'Route Soukra km 2.5 devant la mosquée Sfax', NULL, NULL, '31 191 196', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:30:16'),
(26, 'parapharmacie', 'Mharza Centre Parapharmacy', NULL, NULL, 17, 168, 'km 4 à proximité du complexe médical Mharza Centre, Route Mharza', NULL, NULL, '31 197 382', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:30:49'),
(27, 'parapharmacie', 'Parapharmacie Health Caring', NULL, NULL, 17, 168, 'QPG2+HF4, Rte El Ain', NULL, NULL, '52 562 383', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:31:12'),
(28, 'parapharmacie', 'Parapharmacie Mayar', NULL, NULL, 17, 168, 'PPM5+8QF, Rte de l\'aéroport', NULL, NULL, '58 732 762', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:31:52'),
(29, 'parapharmacie', 'Parapharmacie masmoudi', NULL, NULL, 17, 168, NULL, NULL, NULL, '52 996 662', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:32:15'),
(30, 'parapharmacie', 'Parapharmacie HD Pharma', NULL, NULL, 17, 168, 'Route Gremda km 3.5 kassas', NULL, NULL, '28 499 008', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:32:41'),
(31, 'parapharmacie', 'Parapharmacie Rahma', NULL, NULL, 17, 168, 'Rte Saltnia', NULL, NULL, '23 407 379', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:33:04'),
(32, 'parapharmacie', 'Para Fendri', NULL, NULL, 17, 168, 'devant maison Skoda, Route Teniour km 0.5 pres pharmacie Amel Zahaf Fendri Route Gremda, km 6.5', NULL, NULL, '27 888 590', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:33:58'),
(33, 'parapharmacie', 'LIFE PARA', NULL, NULL, 17, 168, 'km 1 à côté du banque BIAT, Route Sidi mansour km 4 à côté du centre de police cité Bourgiba, Rte Saltnia', NULL, NULL, '28 186 186', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:34:23'),
(34, 'parapharmacie', 'Syntam parapharmacie', NULL, NULL, 17, 168, '2 rue d\'athènes', NULL, NULL, '74 202 877', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:35:03'),
(35, 'parapharmacie', 'Parapharmacie fourpharma gremda', NULL, NULL, 17, 168, 'PQJ2+R2W, 3032 Rte Gremda', NULL, NULL, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:35:40'),
(36, 'parapharmacie', 'Chouayakh Parapharmacie', NULL, NULL, 17, 168, 'PPRW+6JV, Rue El Kawthar', NULL, NULL, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:36:01'),
(37, 'parapharmacie', 'Cosmedic', NULL, NULL, 17, 168, 'Polyclinique el basetine', NULL, NULL, '29 898 984', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:36:29'),
(38, 'parapharmacie', 'Le Trio Médical', NULL, NULL, 17, 168, 'Route gremda km 2 en face complexe médical \"Syphax Médical Sfax', NULL, NULL, '56 728 418', NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:37:04'),
(39, 'parapharmacie', 'PARA GEXEL', NULL, NULL, 17, 168, '187 Av. Habib Bourguiba', 36.1766000, 8.6983000, '55 445 541', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:37:29'),
(40, 'parapharmacie', 'PARASTORE NASRIA', NULL, NULL, 17, 168, 'Bloc B, Rez de chaussée, Imm Ribat, Km0 Rte Gremda', 36.1766000, 8.6983000, '98 763 081', NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 13:38:35'),
(41, 'pharmacy', 'Pharmacien', NULL, NULL, 20, 207, 'H.sousse', 36.1906567, 10.4145717, '53 911 128', 'Uunokiaaa@gmail.com', NULL, 'independent', 'A', 'specialized', 'regular', 0, 'medium', NULL, 1, 4, 4, NULL, 1, '2026-04-09 19:01:41'),
(42, 'doctor', 'Dr Madhi Anis', 'Médecin généraliste', NULL, 20, 205, '49MF+3MH، Unnamed Road, Enfidha', 36.1326875, 10.3742344, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 20:53:29'),
(43, 'doctor', 'Dr Madhi Anis', 'Médecin généraliste', NULL, 20, 205, '49MF+3MH، Unnamed Road, Enfidha', 36.1326875, 10.3742344, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 20:55:26'),
(44, 'doctor', 'Dr Ghadhab', 'Médecin généraliste', NULL, 20, 205, '49MH+24G, Enfidha', 36.1325625, 10.3778281, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 20:58:32'),
(45, 'doctor', 'Dr Abderahmen Hmidi', 'Pédiatre', NULL, 20, 205, '49PG+284, C133, Enfidha', 36.1350125, 10.3758281, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 20:59:14'),
(46, 'doctor', 'Dr Haykel Touil', 'Médecin généraliste', NULL, 20, 205, '49PM+H29, Av de la république, Enfidha', 36.1364125, 10.3826094, NULL, NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 21:00:25'),
(47, 'doctor', 'Dr Baira Rochdi', 'Médecin généraliste', NULL, 20, 205, '49MJ+977, Enfidha', 36.1334125, 10.3806719, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 21:01:12'),
(48, 'doctor', 'Dr Yazidi Amel', 'Gynécologue', NULL, 20, 205, '49MF+2PW, Enfidha', 36.1326125, 10.3743281, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 21:02:22'),
(49, 'doctor', 'Dr Mhedhbi Med Haythem', 'Gynécologue', NULL, 20, 205, '49PJ+24 Enfidha', 36.1350625, 10.3803125, NULL, NULL, NULL, 'independent', 'B', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 21:03:27'),
(50, 'hospital', 'Consultation Externe', NULL, NULL, 20, 205, '49M9+PHG, Unnamed Road, Enfidha', 36.1343125, 10.3689531, NULL, NULL, NULL, 'independent', 'A', 'local', 'new', 0, 'medium', NULL, 30, 3, 1, NULL, 1, '2026-04-09 21:04:20');

-- --------------------------------------------------------

--
-- Table structure for table `governorates`
--

CREATE TABLE `governorates` (
  `id` bigint UNSIGNED NOT NULL,
  `name_fr` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ar` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `governorates`
--

INSERT INTO `governorates` (`id`, `name_fr`, `name_ar`, `latitude`, `longitude`) VALUES
(1, 'Ariana', 'أريانة', NULL, NULL),
(2, 'Beja', 'باجة', NULL, NULL),
(3, 'Ben Arous', 'بن عروس', NULL, NULL),
(4, 'Bizerte', 'بنزرت', NULL, NULL),
(5, 'Gabes', 'قابس', NULL, NULL),
(6, 'Gafsa', 'قفصة', NULL, NULL),
(7, 'Jendouba', 'جندوبة', NULL, NULL),
(8, 'Kairouan', 'القيروان', NULL, NULL),
(9, 'Kasserine', 'القصرين', NULL, NULL),
(10, 'Kebili', 'قبلي', NULL, NULL),
(11, 'Kef', 'الكاف', NULL, NULL),
(12, 'Mahdia', 'المهدية', NULL, NULL),
(13, 'Manouba', 'منوبة', NULL, NULL),
(14, 'Medenine', 'مدنين', NULL, NULL),
(15, 'Monastir', 'المنستير', NULL, NULL),
(16, 'Nabeul', 'نابل', NULL, NULL),
(17, 'Sfax', 'صفاقس', NULL, NULL),
(18, 'Sidi Bouzid', 'سيدي بوزيد', NULL, NULL),
(19, 'Siliana', 'سليانة', NULL, NULL),
(20, 'Sousse', 'سوسة', NULL, NULL),
(21, 'Tataouine', 'تطاوين', NULL, NULL),
(22, 'Tozeur', 'توزر', NULL, NULL),
(23, 'Tunis', 'تونس', NULL, NULL),
(24, 'Zaghouan', 'زغوان', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `phinxlog`
--

CREATE TABLE `phinxlog` (
  `version` bigint NOT NULL,
  `migration_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phinxlog`
--

INSERT INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`) VALUES
(20260408231000, 'CreateUsersTable', '2026-04-09 00:28:17', '2026-04-09 00:28:17', 0),
(20260408231100, 'CreateGovernoratesTable', '2026-04-09 00:28:17', '2026-04-09 00:28:17', 0),
(20260408232000, 'AlignIdColumnsBigint', '2026-04-09 00:48:23', '2026-04-09 00:48:23', 0),
(20260408233000, 'CreateCitiesTable', '2026-04-09 00:51:11', '2026-04-09 00:51:11', 0),
(20260408233100, 'CreateContactsTable', '2026-04-09 00:51:11', '2026-04-09 00:51:12', 0),
(20260408233200, 'AddPostalCodeToCities', '2026-04-09 00:51:12', '2026-04-09 00:51:12', 0),
(20260408233300, 'CreateVisitsTable', '2026-04-09 00:51:12', '2026-04-09 00:51:12', 0),
(20260409010000, 'AddUniqueIndexesGovernoratesCities', '2026-04-09 01:08:54', '2026-04-09 01:08:54', 0),
(20260409012000, 'CreateProductsTable', '2026-04-09 10:50:46', '2026-04-09 10:50:46', 0),
(20260409012100, 'CreateStockTable', '2026-04-09 10:50:46', '2026-04-09 10:50:46', 0),
(20260409012200, 'CreateVisitSamplesTable', '2026-04-09 10:50:46', '2026-04-09 10:50:46', 0),
(20260409012300, 'CreateStockMovementsTable', '2026-04-09 10:50:46', '2026-04-09 10:50:46', 0),
(20260409014000, 'CreateStockGlobalTable', '2026-04-09 12:39:20', '2026-04-09 12:39:20', 0),
(20260409120000, 'AddZoneFieldsToUsers', '2026-04-09 12:39:20', '2026-04-09 12:39:20', 0),
(20260409150000, 'AddMultipleGovernoratesToUsers', '2026-04-09 18:34:02', '2026-04-09 18:34:02', 0),
(20260409160000, 'AddProductFields', '2026-04-09 18:34:02', '2026-04-09 18:34:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `gtin13` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialities` text COLLATE utf8mb4_unicode_ci,
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `photo`, `cost`, `price`, `gtin13`, `specialities`, `sku`, `active`, `created_at`) VALUES
(2, 'MagBoost 60 gélules', NULL, 6.91, 29.32, '6192446205232', NULL, 'MGBST', 1, '2026-04-09 22:27:15');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_global`
--

CREATE TABLE `stock_global` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `visit_id` bigint UNSIGNED DEFAULT NULL,
  `movement_type` enum('add','deduct') COLLATE utf8mb4_unicode_ci DEFAULT 'deduct',
  `quantity` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','rep') COLLATE utf8mb4_unicode_ci DEFAULT 'rep',
  `governorate_id` bigint UNSIGNED DEFAULT NULL,
  `governorate_ids` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of governorate IDs for multi-governorate support',
  `excluded_city_ids` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `governorate_id`, `governorate_ids`, `excluded_city_ids`, `phone`, `zone`, `active`, `last_login`, `created_at`) VALUES
(1, 'Admin', 'admin@reptrack.tn', '$2y$12$/jv5CAZKLB55HLKY2Vlpj.rRP20fFjG.7ZwV8UpBF14XiHYhgSBEK', 'admin', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-04-09 00:00:30'),
(3, 'Montassar Billeh Hazgui', 'hello@giscon.tn', '$2y$12$o1PYVh4QfP4Mcv8.HfnFwO0iJ./0enXSXU.XG9zL9yqhqL.pwuOyy', 'rep', 1, '[1,3,13,16,17,20,23,24]', NULL, '20726000', NULL, 1, NULL, '2026-04-09 10:58:57'),
(4, 'Mootez Billeh Hazgui', 'mootez.hazgui@giscon.tn', '$2y$12$vt/wrHEHQaeHfDRWh8fVjertiuLloYM7XLsZZ6BhRNw8Xu/72Ai3a', 'rep', 18, NULL, NULL, '53911128', NULL, 1, NULL, '2026-04-09 18:37:22');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `contact_id` bigint UNSIGNED DEFAULT NULL,
  `visit_type` enum('rappel','presentation','formation') COLLATE utf8mb4_unicode_ci DEFAULT 'rappel',
  `products_discussed` text COLLATE utf8mb4_unicode_ci,
  `samples_given` text COLLATE utf8mb4_unicode_ci,
  `training_content` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visit_samples`
--

CREATE TABLE `visit_samples` (
  `id` bigint UNSIGNED NOT NULL,
  `visit_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cities_gov_name` (`governorate_id`,`name_fr`),
  ADD KEY `governorate_id` (`governorate_id`),
  ADD KEY `postal_code` (`postal_code`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `governorate_id` (`governorate_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `assigned_rep_id` (`assigned_rep_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `governorates`
--
ALTER TABLE `governorates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_governorates_name_fr` (`name_fr`);

--
-- Indexes for table `phinxlog`
--
ALTER TABLE `phinxlog`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_stock_user_product` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_global`
--
ALTER TABLE `stock_global`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_stock_global_product` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `visit_id` (`visit_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `governorate_id` (`governorate_id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `contact_id` (`contact_id`);

--
-- Indexes for table `visit_samples`
--
ALTER TABLE `visit_samples`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=507;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `governorates`
--
ALTER TABLE `governorates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_global`
--
ALTER TABLE `stock_global`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visit_samples`
--
ALTER TABLE `visit_samples`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`governorate_id`) REFERENCES `governorates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`governorate_id`) REFERENCES `governorates` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `contacts_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `contacts_ibfk_3` FOREIGN KEY (`assigned_rep_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `contacts_ibfk_4` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_global`
--
ALTER TABLE `stock_global`
  ADD CONSTRAINT `stock_global_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_3` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`governorate_id`) REFERENCES `governorates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `visit_samples`
--
ALTER TABLE `visit_samples`
  ADD CONSTRAINT `visit_samples_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `visit_samples_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
