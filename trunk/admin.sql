-- phpMyAdmin SQL Dump
-- version 2.9.0.3
-- http://www.phpmyadmin.net
-- 
-- ����: localhost
-- ��������: 2007 �� 03 �� 29 �� 15:40
-- �������汾: 5.0.27
-- PHP �汾: 4.4.4
-- 
-- ���ݿ�: `admin`
-- 

-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_access`
-- 

CREATE TABLE `think_access` (
  `groupId` smallint(6) unsigned NOT NULL,
  `nodeId` smallint(6) unsigned NOT NULL,
  `level` tinyint(1) NOT NULL,
  `parentNodeId` smallint(6) NOT NULL,
  KEY `groupId` (`groupId`),
  KEY `nodeId` (`nodeId`),
  KEY `level` (`level`),
  KEY `parentNodeId` (`parentNodeId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- �������е����� `think_access`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_attach`
-- 

CREATE TABLE `think_attach` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) default NULL,
  `size` varchar(20) NOT NULL,
  `extension` varchar(20) NOT NULL,
  `savepath` varchar(255) NOT NULL,
  `savename` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `recordId` int(11) NOT NULL,
  `userId` int(11) unsigned default NULL,
  `uploadTime` int(11) unsigned default NULL,
  `downloadTime` mediumint(9) unsigned default NULL,
  `hash` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`,`recordId`),
  KEY `module` (`module`),
  KEY `recordId` (`recordId`),
  KEY `userId` (`userId`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- �������е����� `think_attach`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_cache`
-- 

CREATE TABLE `think_cache` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cachekey` varchar(255) character set utf8 NOT NULL,
  `expire` int(11) NOT NULL,
  `data` blob,
  `datasize` int(11) default NULL,
  `datacrc` varchar(32) character set utf8 default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- �������е����� `think_cache`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_config`
-- 

CREATE TABLE `think_config` (
  `id` mediumint(9) NOT NULL auto_increment,
  `title` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `remark` varchar(255) default NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=44 ;

-- 
-- �������е����� `think_config`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_group`
-- 

CREATE TABLE `think_group` (
  `id` smallint(6) unsigned NOT NULL auto_increment,
  `name` varchar(20) NOT NULL,
  `pid` smallint(6) default NULL,
  `status` tinyint(1) unsigned default NULL,
  `remark` varchar(255) default NULL,
  `ename` varchar(5) default NULL,
  `requireRate` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `parentId` (`pid`),
  KEY `ename` (`ename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- 
-- �������е����� `think_group`
-- 

INSERT INTO `think_group` (`id`, `name`, `pid`, `status`, `remark`, `ename`, `requireRate`) VALUES 
(1, '����Ա', 0, 1, '���й���ԱȨ��', NULL, 0),
(2, '��ͨ�û�', 0, 1, '��ͨ�û�', NULL, 1);

-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_groupuser`
-- 

CREATE TABLE `think_groupuser` (
  `groupId` mediumint(9) unsigned default NULL,
  `userId` mediumint(9) unsigned default NULL,
  KEY `groupId` (`groupId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 
-- �������е����� `think_groupuser`
-- 

INSERT INTO `think_groupuser` (`groupId`, `userId`) VALUES 
(2, 2),
(1, 1);

-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_log`
-- 

CREATE TABLE `think_log` (
  `id` int(11) NOT NULL auto_increment,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `time` varchar(20) default NULL,
  `userId` int(11) default NULL,
  `remark` varchar(500) NOT NULL,
  `url` varchar(500) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- �������е����� `think_log`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_login`
-- 

CREATE TABLE `think_login` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `userId` int(11) unsigned default NULL,
  `inTime` varchar(25) default NULL,
  `loginIp` varchar(50) default NULL,
  `type` tinyint(4) unsigned default NULL,
  `outTime` varchar(25) default NULL,
  PRIMARY KEY  (`id`),
  KEY `userId` (`userId`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- �������е����� `think_login`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_memo`
-- 

CREATE TABLE `think_memo` (
  `id` mediumint(6) unsigned NOT NULL auto_increment,
  `label` varchar(255) NOT NULL,
  `memo` text,
  `createTime` varchar(25) NOT NULL,
  `userId` mediumint(8) NOT NULL,
  `type` varchar(15) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `label` (`label`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- 
-- �������е����� `think_memo`
-- 

-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_node`
-- 

CREATE TABLE `think_node` (
  `id` smallint(6) unsigned NOT NULL auto_increment,
  `name` varchar(20) NOT NULL,
  `title` varchar(50) default NULL,
  `status` tinyint(1) unsigned default NULL,
  `remark` varchar(255) default NULL,
  `seqNo` smallint(6) unsigned default NULL,
  `pid` smallint(6) unsigned NOT NULL,
  `level` tinyint(1) unsigned NOT NULL,
  `type` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `parentId` (`pid`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=28 ;

-- 
-- �������е����� `think_node`
-- 

INSERT INTO `think_node` (`id`, `name`, `title`, `status`, `remark`, `seqNo`, `pid`, `level`, `type`) VALUES 
(1, 'Admin', 'ThinkPHP��̨����', 1, 'ThinkPHPʾ������', 1, 0, 1, 0),
(3, 'User', '�û�����', 1, '', 6, 1, 2, 0),
(5, 'Group', 'Ȩ�޹���', 1, '', 7, 1, 2, 0),
(6, 'PlugIn', '�������', 1, '', 8, 1, 2, 0),
(7, 'Node', '�ڵ����', 1, '', 9, 1, 2, 0),
(8, 'System', 'ϵͳ����', 1, '', 10, 1, 2, 0),
(9, 'DBManager', '���ݿ����', 1, '', 1, 1, 2, 0),
(10, 'Public', '����ģ��', 1, '', 4, 1, 2, 0),
(11, 'Index', 'Ĭ��ģ��', 1, '', 3, 1, 2, 0),
(12, 'add', '����', 1, '', 1, 10, 3, 0),
(13, 'insert', '����', 1, '�������', 2, 10, 3, 0),
(14, 'edit', '�༭', 1, '�༭����', 3, 10, 3, 0),
(15, 'update', '����', 1, '�������', 4, 10, 3, 0),
(16, 'index', '�б�', 1, 'Ĭ�ϲ���', 5, 10, 3, 0),
(17, 'forbid', '����', 1, '���ò���', 6, 10, 3, 0),
(18, 'resume', '�ָ�', 1, '�ָ�����', 7, 10, 3, 0),
(20, 'Node', '�ڵ����', 1, '', NULL, 19, 2, 0),
(23, 'user', '�û�����', 1, '', NULL, 21, 2, 0),
(24, 'HOME', 'Ĭ����Ŀ', 1, '', NULL, 0, 1, 0),
(25, 'UserType', '�û�����', 1, '', 2, 1, 2, 0),
(27, 'User', '�û�����', 1, '', NULL, 26, 2, 0);

-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_plugin`
-- 

CREATE TABLE `think_plugin` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `author` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  `version` varchar(10) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `app` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- �������е����� `think_plugin`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_session`
-- 

CREATE TABLE `think_session` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cachekey` varchar(255) character set utf8 NOT NULL,
  `expire` int(11) NOT NULL,
  `data` blob,
  `datasize` int(11) default NULL,
  `datacrc` varchar(32) character set utf8 default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- �������е����� `think_session`
-- 


-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_user`
-- 

CREATE TABLE `think_user` (
  `id` int(10) NOT NULL auto_increment,
  `nickname` varchar(30) NOT NULL,
  `password` varchar(32) NOT NULL,
  `name` varchar(30) NOT NULL,
  `registerTime` varchar(25) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `remark` varchar(255) default NULL,
  `verify` varchar(32) default NULL,
  `type` int(3) unsigned default NULL,
  `email` varchar(150) default NULL,
  `childId` int(11) unsigned default NULL,
  `lastLoginTime` varchar(25) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `childId` (`childId`),
  KEY `status` (`status`),
  KEY `verify` (`verify`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='�����û���' AUTO_INCREMENT=3 ;

-- 
-- �������е����� `think_user`
-- 

INSERT INTO `think_user` (`id`, `nickname`, `password`, `name`, `registerTime`, `status`, `remark`, `verify`, `type`, `email`, `childId`, `lastLoginTime`) VALUES 
(1, '��������Ա', '21232f297a57a5a743894a0e4a801fc3', 'admin', '1148194044', 1, 'Super Webmaster', '0000', 1, NULL, NULL, '1175151980'),
(2, '�����û�', '21232f297a57a5a743894a0e4a801fc3', 'test', '', 1, '', '1111', 1, NULL, NULL, '1174750565');

-- --------------------------------------------------------

-- 
-- ��Ľṹ `think_usertype`
-- 

CREATE TABLE `think_usertype` (
  `id` tinyint(2) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `remark` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- 
-- �������е����� `think_usertype`
-- 

INSERT INTO `think_usertype` (`id`, `name`, `status`, `remark`) VALUES 
(1, '��̨����', 1, '��̨������Ա'),
(5, '��Ա', 1, '��Ա');
