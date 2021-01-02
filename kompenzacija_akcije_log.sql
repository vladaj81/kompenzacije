-- Table: kompenzacija_akcije_log

-- DROP TABLE kompenzacija_akcije_log;

CREATE TABLE kompenzacija_akcije_log
(
  id serial NOT NULL,
  kompenzacija_id integer NOT NULL,
  akcija akcija,
  radnik integer NOT NULL,
  datum_promene_statusa timestamp without time zone NOT NULL DEFAULT now(),
  CONSTRAINT id_primary_key PRIMARY KEY (id),
  CONSTRAINT fk_kompenzacija_zaglavlje_akcije FOREIGN KEY (kompenzacija_id)
      REFERENCES kompenzacija_zaglavlje (kompenzacija_zaglavlje_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE kompenzacija_akcije_log
  OWNER TO postgres;
