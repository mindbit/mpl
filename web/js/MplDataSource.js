isc.defineClass("MplDataSource", "RestDataSource");
isc.MplDataSource.addProperties({
	dataFormat:"json",

	operationBindings:[
		{operationType:"fetch", dataProtocol:"postMessage"},
		{operationType:"add", dataProtocol:"postMessage"},
		{operationType:"remove", dataProtocol:"postMessage"},
		{operationType:"update", dataProtocol:"postMessage"}
	],

	transformRequest: function (dsRequest) {
		var fields = this.getFields();
		for (var fieldName in fields) {
			var field = fields[fieldName];
			if (field.type == "date" && dsRequest.data[fieldName])
				dsRequest.data[fieldName].logicalDate = true;
			if (field.type == "date" && dsRequest.oldValues && dsRequest.oldValues[fieldName])
				dsRequest.oldValues[fieldName].logicalDate = true;
		}

		return this.Super("transformRequest", [dsRequest]);
	}
});
