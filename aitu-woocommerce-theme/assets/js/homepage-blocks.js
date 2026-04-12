(function (wp) {
	"use strict";

	if (!wp || !wp.blocks || !wp.element || !wp.components) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var blockEditor = wp.blockEditor || wp.editor || {};
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var components = wp.components;
	var Button = components.Button;
	var __ = wp.i18n && wp.i18n.__ ? wp.i18n.__ : function (value) { return value; };
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default
		? wp.serverSideRender.default
		: wp.serverSideRender;

	var productsData = window.aituHomepageBlocksData && Array.isArray(window.aituHomepageBlocksData.products)
		? window.aituHomepageBlocksData.products
		: [];

	var productOptions = [{ label: __("None", "aitu-woocommerce-theme"), value: 0 }];
	productsData.forEach(function (item) {
		var value = parseInt(item && item.value ? item.value : 0, 10) || 0;
		var label = item && item.label ? String(item.label) : ("#" + value);
		productOptions.push({ value: value, label: label });
	});

	function normalizeProductIds(ids, length) {
		var max = Math.max(1, parseInt(length, 10) || 3);
		var source = Array.isArray(ids) ? ids : [];
		var normalized = [];
		var i;

		for (i = 0; i < max; i++) {
			normalized.push(parseInt(source[i] || 0, 10) || 0);
		}

		return normalized;
	}

	function removeCurrentBlock(clientId) {
		if (!clientId || !wp.data || !wp.data.dispatch) {
			return;
		}

		var dispatcher = wp.data.dispatch("core/block-editor");
		if (dispatcher && dispatcher.removeBlock) {
			dispatcher.removeBlock(clientId);
		}
	}

	registerBlockType("aitu/product-row", {
		apiVersion: 2,
		title: __("AITU Product Row", "aitu-woocommerce-theme"),
		icon: "screenoptions",
		category: "widgets",
		attributes: {
			productIds: {
				type: "array",
				default: [0, 0, 0]
			},
			isEditorPreview: {
				type: "boolean",
				default: false
			}
		},
		edit: function (props) {
			var attributes = props.attributes || {};
			var setAttributes = props.setAttributes;
			var className = props.className || "";
			var productIds = normalizeProductIds(attributes.productIds, 3);
			var cardStyle = {
				position: "relative"
			};
			var removeBlockStyle = {
				background: "#000",
				border: "1px solid #000",
				borderRadius: 0,
				boxShadow: "none",
				color: "#fff",
				fontSize: "11px",
				lineHeight: 1.2,
				minHeight: "24px",
				padding: "2px 8px",
				position: "absolute",
				right: "16px",
				top: "6px",
				zIndex: 4
			};

			function setSlot(index, value) {
				var next = normalizeProductIds(productIds, 3);
				next[index] = parseInt(value || 0, 10) || 0;
				setAttributes({ productIds: next });
			}

			return el(
				"div",
				{ className: (className + " aitu-block-editor-card").trim(), style: cardStyle },
				[
					el(Button, {
						key: "remove",
						isSecondary: true,
						style: removeBlockStyle,
						onClick: function () {
							removeCurrentBlock(props.clientId);
						}
					}, __("Remove block", "aitu-woocommerce-theme")),
					el(
						"div",
						{ key: "head", className: "aitu-block-editor-head" },
						[
							el("div", { key: "title", className: "aitu-block-editor-title" }, __("AITU PRODUCT ROW", "aitu-woocommerce-theme"))
						]
					),
					el(
						"div",
						{
							key: "controls",
							className: "aitu-block-editor-controls aitu-block-editor-controls-products",
							style: { width: "100%", marginBottom: "14px" }
						},
						el(
							"div",
							{
								className: "aitu-slot-grid",
								style: {
									display: "grid",
									gridTemplateColumns: "repeat(3, minmax(0, 1fr))",
									columnGap: "12px",
									width: "100%"
								}
							},
							[0, 1, 2].map(function (index) {
								var inputId = "aitu-product-slot-" + (props.clientId || "block") + "-" + index;
								return el(
									"div",
									{
										key: "slot-col-" + index,
										className: "aitu-slot-col",
										style: {
											display: "flex",
											flexDirection: "column",
											minWidth: 0
										}
									},
									[
										el(
											"label",
											{
												key: "label-" + index,
												className: "aitu-slot-col-label",
												htmlFor: inputId,
												style: {
													display: "block",
													fontSize: "11px",
													letterSpacing: "0.05em",
													marginBottom: "6px",
													textTransform: "uppercase"
												}
											},
											"SLOT " + (index + 1)
										),
										el(
											"select",
											{
												key: "select-" + index,
												id: inputId,
												className: "aitu-slot-select",
												value: String(productIds[index]),
												style: {
													width: "100%",
													minHeight: "36px",
													padding: "6px 8px",
													border: "1px solid #000",
													borderRadius: 0,
													fontSize: "12px"
												},
												onChange: function (event) {
													setSlot(index, event && event.target ? event.target.value : 0);
												}
											},
											productOptions.map(function (option) {
												var optionValue = parseInt(option.value || 0, 10) || 0;
												return el(
													"option",
													{ key: "option-" + index + "-" + optionValue, value: String(optionValue) },
													option.label
												);
											})
										)
									]
								);
							})
						)
					),
					ServerSideRender
						? el(
							"div",
							{ key: "preview", className: "aitu-block-editor-live-preview" },
							el(ServerSideRender, {
								block: "aitu/product-row",
								attributes: {
									productIds: productIds,
									isEditorPreview: true
								}
							})
						)
						: el(
							"p",
							{ key: "fallback", className: "aitu-block-editor-fallback" },
							__("Preview unavailable.", "aitu-woocommerce-theme")
						)
				]
			);
		},
		save: function () {
			return null;
		}
	});

	registerBlockType("aitu/banner", {
		apiVersion: 2,
		title: __("AITU Banner", "aitu-woocommerce-theme"),
		icon: "format-image",
		category: "widgets",
		attributes: {
			imageId: {
				type: "number",
				default: 0
			},
			imageUrl: {
				type: "string",
				default: ""
			},
			linkUrl: {
				type: "string",
				default: ""
			}
		},
		edit: function (props) {
			var attributes = props.attributes || {};
			var setAttributes = props.setAttributes;
			var className = props.className || "";
			var imageId = parseInt(attributes.imageId || 0, 10) || 0;
			var imageUrl = attributes.imageUrl ? String(attributes.imageUrl) : "";
			var linkUrl = attributes.linkUrl ? String(attributes.linkUrl) : "";
			var rootStyle = {
				boxSizing: "border-box",
				margin: "40px 0",
				maxWidth: "100%",
				overflowX: "hidden",
				width: "100%"
			};
			var canvasStyle = {
				alignItems: "center",
				backgroundColor: "#fff",
				backgroundPosition: "center center",
				backgroundRepeat: "no-repeat",
				backgroundSize: "contain",
				border: "1px dashed #868686",
				boxSizing: "border-box",
				display: "flex",
				justifyContent: "center",
				minHeight: "420px",
				overflow: "hidden",
				padding: "40px 20px",
				position: "relative",
				maxWidth: "100%",
				width: "100%"
			};
			if (imageUrl) {
				canvasStyle.backgroundImage = 'url("' + imageUrl + '")';
			}
			var overlayStyle = {
				alignItems: "center",
				background: "#fff",
				border: "1px solid #000",
				boxSizing: "border-box",
				color: "#000",
				display: "flex",
				flexDirection: "column",
				gap: "10px",
				margin: "0 auto",
				maxWidth: "560px",
				padding: "16px",
				textAlign: "center",
				width: "100%",
				zIndex: 2
			};
			var titleStyle = {
				fontFamily: "\"IBM Plex Mono\", monospace",
				fontSize: "12px",
				letterSpacing: "0.04em",
				margin: 0,
				textTransform: "uppercase"
			};
			var actionsStyle = {
				alignItems: "center",
				display: "flex",
				flexWrap: "wrap",
				gap: "10px",
				justifyContent: "center",
				marginBottom: "10px"
			};
			var buttonStyle = {
				background: "#fff",
				border: "1px solid #000",
				borderRadius: 0,
				boxShadow: "none",
				color: "#000"
			};
			var linkFieldWrapStyle = {
				textAlign: "left",
				width: "100%"
			};
			var linkLabelStyle = {
				color: "#000",
				display: "block",
				fontFamily: "\"IBM Plex Mono\", monospace",
				fontSize: "11px",
				letterSpacing: "0.04em",
				marginBottom: "6px",
				textTransform: "uppercase"
			};
			var linkInputStyle = {
				background: "#fff",
				border: "1px solid #000",
				borderRadius: 0,
				boxSizing: "border-box",
				color: "#000",
				fontFamily: "\"IBM Plex Mono\", monospace",
				fontSize: "12px",
				height: "36px",
				padding: "6px 8px",
				width: "100%"
			};
			var removeBlockStyle = {
				background: "#000",
				border: "1px solid #000",
				borderRadius: 0,
				boxShadow: "none",
				color: "#fff",
				fontSize: "11px",
				lineHeight: 1.2,
				minHeight: "24px",
				padding: "2px 8px",
				position: "absolute",
				right: "12px",
				top: "0",
				zIndex: 4
			};

			function onSelectImage(media) {
				var nextId = media && media.id ? parseInt(media.id, 10) || 0 : 0;
				var nextUrl = media && media.url ? String(media.url) : "";
				setAttributes({
					imageId: nextId,
					imageUrl: nextUrl
				});
			}

			function removeImage() {
				setAttributes({
					imageId: 0,
					imageUrl: ""
				});
			}

			return el(
				"div",
				{ className: (className + " aitu-block-editor-card").trim(), style: rootStyle },
				[
					el(
						"div",
						{
							key: "canvas",
							className: "aitu-block-editor-banner-canvas",
							style: canvasStyle
						},
						[
							el(Button, {
								key: "remove",
								isSecondary: true,
								style: removeBlockStyle,
								onClick: function () {
									removeCurrentBlock(props.clientId);
								}
							}, __("Remove block", "aitu-woocommerce-theme")),
							el(
								"div",
								{ key: "overlay", className: "aitu-block-editor-banner-overlay", style: overlayStyle },
								[
									el("div", { key: "title", className: "aitu-block-editor-title", style: titleStyle }, __("AITU BANNER", "aitu-woocommerce-theme")),
									el(
										"div",
										{
											key: "controls",
											className: "aitu-block-editor-controls aitu-block-editor-controls-banner",
											style: { width: "100%" }
										},
										[
											el(
												"div",
												{
													key: "media-actions",
													className: "aitu-block-editor-banner-actions",
													style: actionsStyle
												},
												MediaUpload && MediaUploadCheck
													? el(
														MediaUploadCheck,
														{},
														el(MediaUpload, {
															onSelect: onSelectImage,
															allowedTypes: ["image"],
															value: imageId,
															render: function (renderProps) {
																return el(
																	Fragment,
																	{},
																	[
																		el(Button, {
																			key: "select",
																			isSecondary: true,
																			style: buttonStyle,
																			onClick: renderProps.open
																		}, imageUrl ? __("Replace image", "aitu-woocommerce-theme") : __("Select image", "aitu-woocommerce-theme")),
																		imageUrl
																			? el(Button, {
																				key: "remove",
																				isLink: true,
																				isDestructive: true,
																				style: { color: "#000" },
																				onClick: removeImage
																			}, __("Remove image", "aitu-woocommerce-theme"))
																			: null
																	]
																);
															}
														})
													)
													: null
											),
											el(
												"div",
												{ key: "link-wrap", style: linkFieldWrapStyle },
												[
													el("label", { key: "link-label", style: linkLabelStyle }, __("Banner link URL", "aitu-woocommerce-theme")),
													el("input", {
														key: "link",
														type: "url",
														value: linkUrl,
														placeholder: "https://",
														style: linkInputStyle,
														onChange: function (event) {
															setAttributes({
																linkUrl: String(event && event.target ? event.target.value : "")
															});
														}
													})
												]
											)
										]
									)
								]
							),
							imageUrl
								? null
								: el(
									"div",
									{ key: "empty", className: "aitu-block-editor-banner-empty" },
									__("No image selected", "aitu-woocommerce-theme")
								)
						]
					)
				]
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
