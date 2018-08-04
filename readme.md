
Goal: database migrations with:

- Backup before each migration.
- Editable initial migration.
    - Optional (for test or development): automatic database cleanup and rerun of all migrations on initial migration content change.
- Editable last migration.
    - Optional (for test or development): when last migration file is modified, database is automatically restored from backup (see above) and new version of that migration is applied.
- Zero commandline interaction mode.
    - Things should be fast enough to allow checking for new migrations on each request in development enviroments. This way, novice developers will recieve migrations without need to interact with commandline¹.
    - Nice-to-have: some optional basic html UI to handle this workflow in-browser.

Considerations:

- Hacking this type of requirements into [Phinx](https://phinx.org/) would probably mean large rewrite and would interfere with some basic ideas behind Phinx. Therefore I decided to do this as greenfield project (but that decision may be revisited in future).
- This project is intended for small (development) databases.
- This is pure alpha ofc.

---

¹ Which is feared by many, even today (especially those coming from Windows™ background).