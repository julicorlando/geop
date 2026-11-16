try:  # PyMySQL é opcional: só é usado para ler o banco legado na importação.
    import pymysql

    pymysql.install_as_MySQLdb()
except ImportError:  # pragma: no cover
    pass
