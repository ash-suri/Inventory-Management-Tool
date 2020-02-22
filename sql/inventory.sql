CREATE TABLE `inventory_stock` (
  `part_no` varchar(32) NOT NULL,
  `part_name` varchar(255) NOT NULL,
  `part_desc` text,
  `part_stock` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `inventory_stock`
  ADD PRIMARY KEY (`part_no`),
  ADD KEY `part_name` (`part_name`);

CREATE TABLE `inventory_movement` (
  `part_no` varchar(32) NOT NULL,
  `mvt_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mvt_type` varchar(1) NOT NULL,
  `mvt_comment` text,
  `mvt_qty` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `inventory_movement`
  ADD PRIMARY KEY (`part_no`,`mvt_date`);