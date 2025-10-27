--
-- PostgreSQL database dump
--

\restrict rltZZj20KZwdo2fCIrr60gOttZOyfsi5hlBLGVDCizaaj6GNltPIRi8mzj7MCey

-- Dumped from database version 15.14 (Debian 15.14-1.pgdg13+1)
-- Dumped by pg_dump version 15.14 (Debian 15.14-1.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: lite_1; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA lite_1;


ALTER SCHEMA lite_1 OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: lite_1; Owner: postgres
--

CREATE TABLE lite_1.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE lite_1.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: lite_1; Owner: postgres
--

CREATE TABLE lite_1.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE lite_1.cache_locks OWNER TO postgres;

--
-- Name: failed_jobs; Type: TABLE; Schema: lite_1; Owner: postgres
--

CREATE TABLE lite_1.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE lite_1.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: lite_1; Owner: postgres
--

CREATE SEQUENCE lite_1.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE lite_1.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: lite_1; Owner: postgres
--

ALTER SEQUENCE lite_1.failed_jobs_id_seq OWNED BY lite_1.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: lite_1; Owner: postgres
--

CREATE TABLE lite_1.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE lite_1.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: lite_1; Owner: postgres
--

CREATE TABLE lite_1.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE lite_1.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: lite_1; Owner: postgres
--

CREATE SEQUENCE lite_1.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE lite_1.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: lite_1; Owner: postgres
--

ALTER SEQUENCE lite_1.jobs_id_seq OWNED BY lite_1.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: lite_1; Owner: postgres
--

CREATE TABLE lite_1.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE lite_1.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: lite_1; Owner: postgres
--

CREATE SEQUENCE lite_1.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE lite_1.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: lite_1; Owner: postgres
--

ALTER SEQUENCE lite_1.migrations_id_seq OWNED BY lite_1.migrations.id;


--
-- Name: sessions; Type: TABLE; Schema: lite_1; Owner: postgres
--

CREATE TABLE lite_1.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE lite_1.sessions OWNER TO postgres;

--
-- Name: failed_jobs id; Type: DEFAULT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.failed_jobs ALTER COLUMN id SET DEFAULT nextval('lite_1.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.jobs ALTER COLUMN id SET DEFAULT nextval('lite_1.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.migrations ALTER COLUMN id SET DEFAULT nextval('lite_1.migrations_id_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: lite_1; Owner: postgres
--

COPY lite_1.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: lite_1; Owner: postgres
--

COPY lite_1.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: lite_1; Owner: postgres
--

COPY lite_1.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: lite_1; Owner: postgres
--

COPY lite_1.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: lite_1; Owner: postgres
--

COPY lite_1.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: lite_1; Owner: postgres
--

COPY lite_1.migrations (id, migration, batch) FROM stdin;
1	0000_00_00_000000_create_api_accesses_table	1
2	0000_00_00_000000_create_cache_table	1
3	0000_00_00_000000_create_log_histories_table	1
4	0000_00_00_000000_create_unicodes_table	1
5	0000_00_00_000000_create_workspaces_table	1
6	0000_00_00_000001_create_activities_table	1
7	0000_00_00_000001_create_personal_access_tokens_table	1
8	0000_00_00_000002_create_activity_statuses_table	1
9	0000_00_00_000002_create_permissions_table	1
10	0000_00_00_000005_create_domains_table	1
11	0000_00_00_000006_create_tenants_table	1
12	0000_00_00_000008_create_model_has_relations_table	1
13	0000_00_00_000009_create_payload_monitorings_table	1
14	0001_01_01_000000_create_users_table	1
15	0001_01_01_000001_create_user_references_table	1
16	0001_01_01_000002_create_add_column_to_user_references_table	1
17	0001_01_01_000002_create_jobs_table	1
18	0001_01_01_000002_create_provinces_table	1
19	0001_01_01_000003_create_districts_table	1
20	0001_01_01_000003_create_subdistricts_table	1
21	0001_01_01_000004_create_villages_table	1
22	0001_01_01_000005_create_addresses_table	1
23	0001_01_01_000005_create_countries_table	1
24	2024_10_20_032227_create_sessions_table	1
25	2024_10_20_032229_create_model_has_encoding_table	1
26	2025_09_24_000000_create_versions_table	1
27	2025_09_24_000001_create_installed_features_table	1
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: lite_1; Owner: postgres
--

COPY lite_1.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: lite_1; Owner: postgres
--

SELECT pg_catalog.setval('lite_1.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: lite_1; Owner: postgres
--

SELECT pg_catalog.setval('lite_1.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: lite_1; Owner: postgres
--

SELECT pg_catalog.setval('lite_1.migrations_id_seq', 27, true);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: lite_1; Owner: postgres
--

ALTER TABLE ONLY lite_1.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: lite_1; Owner: postgres
--

CREATE INDEX jobs_queue_index ON lite_1.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: lite_1; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON lite_1.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: lite_1; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON lite_1.sessions USING btree (user_id);


--
-- PostgreSQL database dump complete
--

\unrestrict rltZZj20KZwdo2fCIrr60gOttZOyfsi5hlBLGVDCizaaj6GNltPIRi8mzj7MCey

