-- OPPRETTE FUNKSJON
DELIMITER $$

CREATE FUNCTION update_samtykke_deltaker_count(
	P_id INT
)
RETURNS INT
NOT DETERMINISTIC
BEGIN
    UPDATE `samtykke_deltaker`
	SET `antall_innslag` = (
		SELECT COUNT(`sdi_id`) 
        FROM `samtykke_deltaker_innslag` WHERE `p_id` = P_id
	)
	WHERE `p_id` = P_id;
    RETURN 0;
END $$
DELIMITER ;

-- HVIS DENNE SKAL SLETTES:
-- DROP FUNCTION IF EXISTS update_samtykke_deltaker_count;