### Filen kommer fra UKMNorge/UKMdeltakere
INSERT INTO `log_actions` (`log_action_id`, `log_action_verb`, `log_action_element`, `log_action_datatype`, `log_action_identifier`, `log_action_printobject`)
VALUES
	(501, 'opprettet', 'tittel for scene', 'int', 'smartukm_titles_scene|create', 0),
	(502, 'endret', 'tittelnavn', 'text', 'smartukm_titles_scene|t_name', 1),
	(503, 'endret', 'lengde', 'int', 'smartukm_titles_scene|t_time', 1),
	(504, 'endret', 'sangtype', 'bool', 'smartukm_titles_scene|t_instrumental', 1),
	(505, 'endret', 'selvlaget', 'bool', 'smartukm_titles_scene|t_selfmade', 1),
	(506, 'endret', 'tekst av', 'text', 'smartukm_titles_scene|t_titleby', 1),
	(507, 'endret', 'melodi av', 'text', 'smartukm_titles_scene|t_musicby', 1),
	(508, 'endret', 'koreografi av', 'text', 'smartukm_titles_scene|t_coreography', 1),
	(509, 'endret', 'om teksten skal leses opp', 'bool', 'smartukm_titles_scene|t_litterature_read', 1),
	(510, 'opprettet', 'film-tittel', 'int', 'smartukm_titles_video|create', 0),
	(511, 'endret', 'navn', 'text', 'smartukm_titles_video|t_v_title', 1),
	(512, 'endret', 'varighet av film', 'int', 'smartukm_titles_video|t_v_time', 1),
	(513, 'endret', 'filmformat', 'text', 'smartukm_titles_video|t_v_format', 1),
	(514, 'opprettet', 'utstillingstittel', 'int', 'smartukm_titles_exhibition|create', 0),
	(515, 'endret', 'navn', 'text', 'smartukm_titles_exhibition|t_e_title', 1),
	(516, 'endret', 'type og teknikk', 'text', 'smartukm_titles_exhibtion|t_e_type', 1),
	(517, 'endret', 'beskrivelse', 'text', 'smartukm_titles_exhibition|t_e_comments', 1);

	(218,'endret','lenke','text','smartukm_place|pl_link',1),
	(111,'lagt til','kontaktperson','text','smartukm_rel_pl_ab|new',1),
	(112,'lagt til','kommune','text','smartukm_rel_pl_k|new',1),
	(113,'lagt til','skjema for videresending','text','smartukm_place|pl_form',0),
	(114,'fjernet','kommune','text','smartukm_rel_pl_k|delete',1),
	(115,'avlyste','mønstringen','text','smartukm_rel_pl|delete',0);

	(319,'meldte av','innslaget fra','int','smartukm_forward_band|delete',1),
	(318,'meldte på','innslaget til','int','smartukm_forward_band|insert',1),
	(413,'meldte på','tittelen til','int','forward_person|insert',1),
	(414,'meldte av','tittelen fra','int','forward_person|delete',1),
	(518,'meldte på','tittelen til','int','forward_title|insert',1),
	(519,'meldte av','tittelen fra','int','forward_title|delete',1);	
	(219,'la til','innslag i forestilling','int','concert|add',1),
	(220,'fjernet','innslag fra forestilling','int','concert|remove',1);