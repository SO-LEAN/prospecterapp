# The tables of the bounded contexts (ADR 0038, 0048), the shape apps/api/src/Shared/Persistence/Tables.php gives
# them in MiniStack: PK/SK, the indexes GSI1 and GSI2 projecting everything, those finding an aggregate NAME, EMAIL
# (ADR 0071) and PHONE (ADR 0072) projecting the keys only, billed by request. They are named
# prospecter-<instance>-api-<context>: the API finds them with DYNAMODB_TABLE_PREFIX=prospecter-<instance>-api.
locals {
  contexts = toset(["accounts", "personal-data", "prospecting"])
  indexes = {
    GSI1  = { partition = "GSI1PK", sort = "GSI1SK", projection = "ALL" }
    GSI2  = { partition = "GSI2PK", sort = "GSI2SK", projection = "ALL" }
    NAME  = { partition = "NAMEPK", sort = "NAMESK", projection = "KEYS_ONLY" }
    EMAIL = { partition = "EMAILPK", sort = "EMAILSK", projection = "KEYS_ONLY" }
    PHONE = { partition = "PHONEPK", sort = "PHONESK", projection = "KEYS_ONLY" }
  }
}

resource "aws_dynamodb_table" "context" {
  for_each = local.contexts

  name         = "prospecter-${var.instance}-api-${each.key}"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "PK"
  range_key    = "SK"

  # The data of the members: never deleted by a plan, recoverable to the second over the last 35 days
  deletion_protection_enabled = true

  point_in_time_recovery {
    enabled = true
  }

  dynamic "attribute" {
    for_each = toset(concat(["PK", "SK"], flatten([for index in values(local.indexes) : [index.partition, index.sort]])))

    content {
      name = attribute.value
      type = "S"
    }
  }

  dynamic "global_secondary_index" {
    for_each = local.indexes

    content {
      name            = global_secondary_index.key
      projection_type = global_secondary_index.value.projection

      key_schema {
        attribute_name = global_secondary_index.value.partition
        key_type       = "HASH"
      }

      key_schema {
        attribute_name = global_secondary_index.value.sort
        key_type       = "RANGE"
      }
    }
  }
}
