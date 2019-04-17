SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `level` (`id`, `l1`, `l2`, `l3`, `l4`, `name`) VALUES
(1,	1,	NULL,	NULL,	NULL,	'Root'),
(2,	1,	1,	NULL,	NULL,	'BranchA'),
(3,	1,	2,	NULL,	NULL,	'BranchB'),
(4,	1,	3,	NULL,	NULL,	'BranchC'),
(5,	1,	4,	NULL,	NULL,	'BranchD'),
(6,	1,	2,	1,	NULL,	'BranchBA'),
(7,	1,	2,	1,	1,	'BranchBAA'),
(8,	1,	3,	1,	NULL,	'BranchCA'),
(9,	1,	3,	2,	NULL,	'BranchCB'),
(10,	1,	3,	1,	1,	'BranchCAA'),
(11,	1,	3,	1,	2,	'BranchCAB'),
(12,	1,	3,	2,	1,	'BranchCBA'),
(13,	1,	3,	2,	2,	'BranchCBB');

INSERT INTO `order` (`id`, `order`, `depth`, `parent`, `name`) VALUES
(1,	1,	1,	NULL,	'Root'),
(2,	2,	2,	1,	'BranchA'),
(3,	3,	2,	1,	'BranchB'),
(4,	6,	2,	1,	'BranchC'),
(5,	13,	2,	1,	'BranchD'),
(6,	4,	3,	3,	'BranchBA'),
(7,	5,	4,	6,	'BranchBAA'),
(8,	7,	3,	4,	'BranchCA'),
(9,	10,	3,	4,	'BranchCB'),
(10,	8,	4,	8,	'BranchCAA'),
(11,	9,	4,	8,	'BranchCAB'),
(12,	11,	4,	9,	'BranchCBA'),
(13,	12,	4,	9,	'BranchCBB');

INSERT INTO `recursive` (`id`, `parent`, `name`) VALUES
(1,	NULL,	'Root'),
(2,	1,	'BranchA'),
(3,	1,	'BranchB'),
(4,	1,	'BranchC'),
(5,	1,	'BranchD'),
(6,	3,	'BranchBA'),
(7,	6,	'BranchBAA'),
(8,	4,	'BranchCA'),
(9,	4,	'BranchCB'),
(10,	8,	'BranchCAA'),
(11,	8,	'BranchCAB'),
(12,	9,	'BranchCBA'),
(13,	9,	'BranchCBB');

INSERT INTO `traversal` (`id`, `left`, `right`, `parent`, `name`) VALUES
(1,	1,	26,	NULL,	'Root'),
(2,	2,	3,	1,	'BranchA'),
(3,	4,	9,	1,	'BranchB'),
(4,	10,	23,	1,	'BranchC'),
(5,	24,	25,	1,	'BranchD'),
(6,	5,	8,	3,	'BranchBA'),
(7,	6,	7,	6,	'BranchBAA'),
(8,	11,	16,	4,	'BranchCA'),
(9,	17,	22,	4,	'BranchCB'),
(10,	12,	13,	8,	'BranchCAA'),
(11,	14,	15,	8,	'BranchCAB'),
(12,	18,	19,	9,	'BranchCBA'),
(13,	20,	21,	9,	'BranchCBB');
