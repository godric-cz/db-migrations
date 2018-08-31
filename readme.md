
Configuration: See possible options in [configuration class](src/Config.php). Options are read from associative array passed to `DbMigrations` constructor.

Goal: database migrations with:

- Backup before each migration.
- Editable initial migration.
    - Optional (for test or development): automatic database cleanup and rerun of all migrations on initial migration content change.
    - TODO this is for distributing modified initial migration for tests to other devs.
- Editable last migration.
    - Optional (for test or development): when last migration file is modified, database is automatically restored from backup (see above) and new version of that migration is applied.
    - TODO maybe wrong idea, which led to abandon original implementation - you usually don't do this and do SQLs from adminer and just copy-paste it to migration then.
- Zero commandline interaction mode.
    - Things should be fast enough to allow checking for new migrations on each request in development enviroments. This way, novice developers will recieve migrations without need to interact with commandline¹.
    - Nice-to-have: some optional basic html UI to handle this workflow in-browser.

Considerations:

- Hacking this type of requirements into [Phinx](https://phinx.org/) would probably mean large rewrite and would interfere with some basic ideas behind Phinx. Therefore I decided to do this as greenfield project (but that decision may be revisited in future).
- This project is intended for small (development) databases.
- This is pure alpha ofc.

---

¹ Which is feared by many, even today (especially those coming from Windows™ background).
