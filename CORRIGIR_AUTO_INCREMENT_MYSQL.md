# Corrigir AUTO_INCREMENT no MySQL

Erro observado:

```text
Duplicate entry '0' for key 'PRIMARY'
```

Isso acontece quando a coluna `id` nao esta configurada como `AUTO_INCREMENT`.

Execute no MySQL:

```sql
ALTER TABLE feedback_workshop
MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
```

Se o MySQL reclamar que ja existe registro com `id = 0`, execute antes:

```sql
UPDATE feedback_workshop
SET id = (SELECT next_id FROM (SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM feedback_workshop) AS x)
WHERE id = 0;
```

Depois rode novamente:

```sql
ALTER TABLE feedback_workshop
MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
```

Conferencia:

```sql
SHOW COLUMNS FROM feedback_workshop LIKE 'id';
```

O resultado precisa mostrar `auto_increment` na coluna `Extra`.
