Nous évoluons enfin le message est reçu mais nous avons à gérer une nouvelle erreur:
{
"errors": [
{
"message": "Internal server error",
"locations": [
{
"line": 4,
"column": 19
}
],
"path": [
"sendWhatsAppTemplateV2",
"success"
],
"extensions": {
"debugMessage": "Cannot return null for non-nullable field \"SendTemplateResult.success\".",
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 899,
"trace": [
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 794,
"call": "GraphQL\\Executor\\ReferenceExecutor::completeValue(GraphQLType: Boolean!, instance of ArrayObject(1), instance of GraphQL\\Type\\Definition\\ResolveInfo, array(2), array(2), null, instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 672,
"call": "GraphQL\\Executor\\ReferenceExecutor::completeValueCatchingError(GraphQLType: Boolean!, instance of ArrayObject(1), instance of GraphQL\\Type\\Definition\\ResolveInfo, array(2), array(2), null, instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 1375,
"call": "GraphQL\\Executor\\ReferenceExecutor::resolveField(GraphQLType: SendTemplateResult, instance of App\\GraphQL\\Types\\WhatsApp\\SendTemplateResult, instance of ArrayObject(1), 'success', array(2), array(2), instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 1314,
"call": "GraphQL\\Executor\\ReferenceExecutor::executeFields(GraphQLType: SendTemplateResult, instance of App\\GraphQL\\Types\\WhatsApp\\SendTemplateResult, array(1), array(1), instance of ArrayObject(3), instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 1265,
"call": "GraphQL\\Executor\\ReferenceExecutor::collectAndExecuteSubfields(GraphQLType: SendTemplateResult, instance of ArrayObject(1), array(1), array(1), instance of App\\GraphQL\\Types\\WhatsApp\\SendTemplateResult, instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 939,
"call": "GraphQL\\Executor\\ReferenceExecutor::completeObjectValue(GraphQLType: SendTemplateResult, instance of ArrayObject(1), instance of GraphQL\\Type\\Definition\\ResolveInfo, array(1), array(1), instance of App\\GraphQL\\Types\\WhatsApp\\SendTemplateResult, instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 889,
"call": "GraphQL\\Executor\\ReferenceExecutor::completeValue(GraphQLType: SendTemplateResult, instance of ArrayObject(1), instance of GraphQL\\Type\\Definition\\ResolveInfo, array(1), array(1), instance of App\\GraphQL\\Types\\WhatsApp\\SendTemplateResult, instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 794,
"call": "GraphQL\\Executor\\ReferenceExecutor::completeValue(GraphQLType: SendTemplateResult!, instance of ArrayObject(1), instance of GraphQL\\Type\\Definition\\ResolveInfo, array(1), array(1), instance of App\\GraphQL\\Types\\WhatsApp\\SendTemplateResult, instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 672,
"call": "GraphQL\\Executor\\ReferenceExecutor::completeValueCatchingError(GraphQLType: SendTemplateResult!, instance of ArrayObject(1), instance of GraphQL\\Type\\Definition\\ResolveInfo, array(1), array(1), instance of App\\GraphQL\\Types\\WhatsApp\\SendTemplateResult, instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 549,
"call": "GraphQL\\Executor\\ReferenceExecutor::resolveField(GraphQLType: Mutation, null, instance of ArrayObject(1), 'sendWhatsAppTemplateV2', array(1), array(1), instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 995,
"call": "GraphQL\\Executor\\ReferenceExecutor::GraphQL\\Executor\\{closure}(array(0), 'sendWhatsAppTemplateV2')"
},
{
"call": "GraphQL\\Executor\\ReferenceExecutor::GraphQL\\Executor\\{closure}(array(0), 'sendWhatsAppTemplateV2')"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 987,
"function": "array_reduce(array(1), instance of Closure, array(0))"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 543,
"call": "GraphQL\\Executor\\ReferenceExecutor::promiseReduce(array(1), instance of Closure, array(0))"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 318,
"call": "GraphQL\\Executor\\ReferenceExecutor::executeFieldsSerially(GraphQLType: Mutation, null, array(0), array(0), instance of ArrayObject(1), instance of App\\GraphQL\\Context\\GraphQLContext)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/ReferenceExecutor.php",
"line": 258,
"call": "GraphQL\\Executor\\ReferenceExecutor::executeOperation(instance of GraphQL\\Language\\AST\\OperationDefinitionNode, null)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/Executor\/Executor.php",
"line": 184,
"call": "GraphQL\\Executor\\ReferenceExecutor::doExecute()"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/GraphQL.php",
"line": 163,
"call": "GraphQL\\Executor\\Executor::promiseToExecute(instance of GraphQL\\Executor\\Promise\\Adapter\\SyncPromiseAdapter, instance of GraphQL\\Type\\Schema, instance of GraphQL\\Language\\AST\\DocumentNode, null, instance of App\\GraphQL\\Context\\GraphQLContext, array(1), null, instance of Closure)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/vendor\/webonyx\/graphql-php\/src\/GraphQL.php",
"line": 97,
"call": "GraphQL\\GraphQL::promiseToExecute(instance of GraphQL\\Executor\\Promise\\Adapter\\SyncPromiseAdapter, instance of GraphQL\\Type\\Schema, '\n mutation SendWhatsAppTemplate($input: SendTemplateInput!) {\n sendWhatsAppTemplateV2(input: $input) {\n success\n messageId\n error\n }\n }\n ', null, instance of App\\GraphQL\\Context\\GraphQLContext, array(1), null, instance of Closure, null)"
},
{
"file": "\/Users\/ns2poportable\/Desktop\/phone-numbers-seg\/public\/graphql.php",
"line": 517,
"call": "GraphQL\\GraphQL::executeQuery(instance of GraphQL\\Type\\Schema, '\n mutation SendWhatsAppTemplate($input: SendTemplateInput!) {\n sendWhatsAppTemplateV2(input: $input) {\n success\n messageId\n error\n }\n }\n ', null, instance of App\\GraphQL\\Context\\GraphQLContext, array(1), null, instance of Closure)"
}
]
}
}
]
}
