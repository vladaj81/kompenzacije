-- Table: kompenzacija_zaglavlje

-- DROP TABLE kompenzacija_zaglavlje;

CREATE TABLE kompenzacija_zaglavlje
(
  kompenzacija_zaglavlje_id serial NOT NULL,
  datum_stanja date NOT NULL,
  partner character varying(13) NOT NULL,
  broj_kompenzacije character varying(10) NOT NULL,
  sistemski_broj integer NOT NULL,
  sistemska_godina integer NOT NULL,
  datum_slanja_pdfa date,
  datum_vracanja_pdfa date,
  CONSTRAINT kompenzacija_zaglavlje_id_pkey PRIMARY KEY (kompenzacija_zaglavlje_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE kompenzacija_zaglavlje
  OWNER TO postgres;
