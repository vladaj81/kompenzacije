-- Table: kompenzacija_stavke

-- DROP TABLE kompenzacija_stavke;

CREATE TABLE kompenzacija_stavke
(
  kompenzacija_stavke_id serial NOT NULL,
  kompenzacija_zaglavlje_id integer NOT NULL,
  konto character varying(15) NOT NULL,
  brojdok character varying(20) NOT NULL,
  duguje numeric(14,2) NOT NULL,
  potrazuje numeric(14,2) NOT NULL,
  kanal_prodaje integer,
  CONSTRAINT kompenzacija_stavke_id_pkey PRIMARY KEY (kompenzacija_stavke_id),
  CONSTRAINT fk_kompenzacija_zaglavlje_stavke FOREIGN KEY (kompenzacija_zaglavlje_id)
      REFERENCES kompenzacija_zaglavlje (kompenzacija_zaglavlje_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE kompenzacija_stavke
  OWNER TO postgres;
