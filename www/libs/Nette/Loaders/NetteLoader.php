<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Loaders
 */



/**
 * Nette auto loader is responsible for loading Nette classes and interfaces.
 *
 * @author     David Grudl
 */
class NetteLoader extends AutoLoader
{
	/** @var NetteLoader */
	private static $instance;

	/** @var array */
	public $list = array(
		'abortexception' => '/Application/Exceptions/AbortException.php',
		'ambiguousserviceexception' => '/Environment/ServiceLocator.php',
		'annotation' => '/Reflection/Annotation.php',
		'annotations' => '/Reflection/Annotations.php',
		'annotationsparser' => '/Reflection/AnnotationsParser.php',
		'appform' => '/Application/AppForm.php',
		'application' => '/Application/Application.php',
		'applicationexception' => '/Application/Exceptions/ApplicationException.php',
		'argumentoutofrangeexception' => '/Utils/exceptions.php',
		'arraylist' => '/Collections/ArrayList.php',
		'arraytools' => '/Utils/ArrayTools.php',
		'authenticationexception' => '/Security/AuthenticationException.php',
		'autoloader' => '/Loaders/AutoLoader.php',
		'badrequestexception' => '/Application/Exceptions/BadRequestException.php',
		'badsignalexception' => '/Application/Exceptions/BadSignalException.php',
		'basetemplate' => '/Templates/BaseTemplate.php',
		'button' => '/Forms/Controls/Button.php',
		'cache' => '/Caching/Cache.php',
		'cachinghelper' => '/Templates/Filters/CachingHelper.php',
		'callback' => '/Utils/Callback.php',
		'checkbox' => '/Forms/Controls/Checkbox.php',
		'classreflection' => '/Reflection/ClassReflection.php',
		'clirouter' => '/Application/Routers/CliRouter.php',
		'collection' => '/Collections/Collection.php',
		'component' => '/ComponentModel/Component.php',
		'componentcontainer' => '/ComponentModel/ComponentContainer.php',
		'config' => '/Config/Config.php',
		'configadapterini' => '/Config/ConfigAdapterIni.php',
		'configurator' => '/Environment/Configurator.php',
		'control' => '/Application/Control.php',
		'conventionalrenderer' => '/Forms/Renderers/ConventionalRenderer.php',
		'curlybracketsfilter' => '/Templates/Filters/LatteFilter.php',
		'curlybracketsmacros' => '/Templates/Filters/LatteFilter.php',
		'datetime53' => '/Utils/DateTime.php',
		'debug' => '/Debug/Debug.php',
		'deprecatedexception' => '/Utils/exceptions.php',
		'directorynotfoundexception' => '/Utils/exceptions.php',
		'downloadresponse' => '/Application/Responses/DownloadResponse.php',
		'dummystorage' => '/Caching/DummyStorage.php',
		'environment' => '/Environment/Environment.php',
		'extensionreflection' => '/Reflection/ExtensionReflection.php',
		'fatalerrorexception' => '/Utils/exceptions.php',
		'filenotfoundexception' => '/Utils/exceptions.php',
		'filestorage' => '/Caching/FileStorage.php',
		'fileupload' => '/Forms/Controls/FileUpload.php',
		'forbiddenrequestexception' => '/Application/Exceptions/ForbiddenRequestException.php',
		'form' => '/Forms/Form.php',
		'formcontainer' => '/Forms/FormContainer.php',
		'formcontrol' => '/Forms/Controls/FormControl.php',
		'formgroup' => '/Forms/FormGroup.php',
		'forwardingresponse' => '/Application/Responses/ForwardingResponse.php',
		'framework' => '/Utils/Framework.php',
		'freezableobject' => '/Utils/FreezableObject.php',
		'ftp' => '/Web/Ftp.php',
		'ftpexception' => '/Web/Ftp.php',
		'functionreflection' => '/Reflection/FunctionReflection.php',
		'genericrecursiveiterator' => '/Utils/Iterators/GenericRecursiveIterator.php',
		'hashtable' => '/Collections/Hashtable.php',
		'hiddenfield' => '/Forms/Controls/HiddenField.php',
		'html' => '/Web/Html.php',
		'httpcontext' => '/Web/HttpContext.php',
		'httprequest' => '/Web/HttpRequest.php',
		'httpresponse' => '/Web/HttpResponse.php',
		'httpuploadedfile' => '/Web/HttpUploadedFile.php',
		'iannotation' => '/Reflection/IAnnotation.php',
		'iauthenticator' => '/Security/IAuthenticator.php',
		'iauthorizator' => '/Security/IAuthorizator.php',
		'icachestorage' => '/Caching/ICacheStorage.php',
		'icollection' => '/Collections/ICollection.php',
		'icomponent' => '/ComponentModel/IComponent.php',
		'icomponentcontainer' => '/ComponentModel/IComponentContainer.php',
		'iconfigadapter' => '/Config/IConfigAdapter.php',
		'idebuggable' => '/Debug/IDebuggable.php',
		'identity' => '/Security/Identity.php',
		'ifiletemplate' => '/Templates/IFileTemplate.php',
		'iformcontrol' => '/Forms/IFormControl.php',
		'iformrenderer' => '/Forms/IFormRenderer.php',
		'ihttprequest' => '/Web/IHttpRequest.php',
		'ihttpresponse' => '/Web/IHttpResponse.php',
		'iidentity' => '/Security/IIdentity.php',
		'ilist' => '/Collections/IList.php',
		'image' => '/Utils/Image.php',
		'imagebutton' => '/Forms/Controls/ImageButton.php',
		'imagemagick' => '/Utils/ImageMagick.php',
		'imailer' => '/Mail/IMailer.php',
		'imap' => '/Collections/IMap.php',
		'inamingcontainer' => '/Forms/INamingContainer.php',
		'instancefilteriterator' => '/Utils/Iterators/InstanceFilterIterator.php',
		'instantclientscript' => '/Forms/Renderers/InstantClientScript.php',
		'invalidlinkexception' => '/Application/Exceptions/InvalidLinkException.php',
		'invalidpresenterexception' => '/Application/Exceptions/InvalidPresenterException.php',
		'invalidstateexception' => '/Utils/exceptions.php',
		'ioexception' => '/Utils/exceptions.php',
		'ipartiallyrenderable' => '/Application/IRenderable.php',
		'ipermissionassertion' => '/Security/IPermissionAssertion.php',
		'ipresenter' => '/Application/IPresenter.php',
		'ipresenterloader' => '/Application/IPresenterLoader.php',
		'ipresenterresponse' => '/Application/IPresenterResponse.php',
		'irenderable' => '/Application/IRenderable.php',
		'iresource' => '/Security/IResource.php',
		'irole' => '/Security/IRole.php',
		'irouter' => '/Application/IRouter.php',
		'iservicelocator' => '/Environment/IServiceLocator.php',
		'iset' => '/Collections/ISet.php',
		'isignalreceiver' => '/Application/ISignalReceiver.php',
		'istatepersistent' => '/Application/IStatePersistent.php',
		'isubmittercontrol' => '/Forms/ISubmitterControl.php',
		'itemplate' => '/Templates/ITemplate.php',
		'itranslator' => '/Utils/ITranslator.php',
		'iuser' => '/Web/IUser.php',
		'jsonresponse' => '/Application/Responses/JsonResponse.php',
		'keynotfoundexception' => '/Collections/Hashtable.php',
		'lattefilter' => '/Templates/Filters/LatteFilter.php',
		'lattemacros' => '/Templates/Filters/LatteMacros.php',
		'limitedscope' => '/Loaders/LimitedScope.php',
		'link' => '/Application/Link.php',
		'mail' => '/Mail/Mail.php',
		'mailmimepart' => '/Mail/MailMimePart.php',
		'memberaccessexception' => '/Utils/exceptions.php',
		'memcachedstorage' => '/Caching/MemcachedStorage.php',
		'methodreflection' => '/Reflection/MethodReflection.php',
		'multirouter' => '/Application/Routers/MultiRouter.php',
		'multiselectbox' => '/Forms/Controls/MultiSelectBox.php',
		'nclosurefix' => '/Utils/Framework.php',
		'netteloader' => '/Loaders/NetteLoader.php',
		'notimplementedexception' => '/Utils/exceptions.php',
		'notsupportedexception' => '/Utils/exceptions.php',
		'object' => '/Utils/Object.php',
		'objectmixin' => '/Utils/ObjectMixin.php',
		'paginator' => '/Utils/Paginator.php',
		'parameterreflection' => '/Reflection/ParameterReflection.php',
		'permission' => '/Security/Permission.php',
		'presenter' => '/Application/Presenter.php',
		'presentercomponent' => '/Application/PresenterComponent.php',
		'presentercomponentreflection' => '/Application/PresenterComponentReflection.php',
		'presenterloader' => '/Application/PresenterLoader.php',
		'presenterrequest' => '/Application/PresenterRequest.php',
		'propertyreflection' => '/Reflection/PropertyReflection.php',
		'radiolist' => '/Forms/Controls/RadioList.php',
		'recursivecomponentiterator' => '/ComponentModel/ComponentContainer.php',
		'redirectingresponse' => '/Application/Responses/RedirectingResponse.php',
		'renderresponse' => '/Application/Responses/RenderResponse.php',
		'robotloader' => '/Loaders/RobotLoader.php',
		'route' => '/Application/Routers/Route.php',
		'rule' => '/Forms/Rule.php',
		'rules' => '/Forms/Rules.php',
		'safestream' => '/Utils/SafeStream.php',
		'selectbox' => '/Forms/Controls/SelectBox.php',
		'sendmailmailer' => '/Mail/SendmailMailer.php',
		'servicelocator' => '/Environment/ServiceLocator.php',
		'session' => '/Web/Session.php',
		'sessionnamespace' => '/Web/SessionNamespace.php',
		'set' => '/Collections/Set.php',
		'simpleauthenticator' => '/Security/SimpleAuthenticator.php',
		'simplerouter' => '/Application/Routers/SimpleRouter.php',
		'smartcachingiterator' => '/Utils/Iterators/SmartCachingIterator.php',
		'snippethelper' => '/Templates/Filters/SnippetHelper.php',
		'string' => '/Utils/String.php',
		'submitbutton' => '/Forms/Controls/SubmitButton.php',
		'template' => '/Templates/Template.php',
		'templatecachestorage' => '/Templates/TemplateCacheStorage.php',
		'templatefilters' => '/Templates/Filters/TemplateFilters.php',
		'templatehelpers' => '/Templates/Filters/TemplateHelpers.php',
		'textarea' => '/Forms/Controls/TextArea.php',
		'textbase' => '/Forms/Controls/TextBase.php',
		'textinput' => '/Forms/Controls/TextInput.php',
		'tools' => '/Utils/Tools.php',
		'uri' => '/Web/Uri.php',
		'uriscript' => '/Web/UriScript.php',
		'user' => '/Web/User.php',
	);



	/**
	 * Returns singleton instance with lazy instantiation.
	 * @return NetteLoader
	 */
	public static function getInstance()
	{
		if (self::$instance === NULL) {
			self::$instance = new self;
		}
		return self::$instance;
	}



	/**
	 * Handles autoloading of classes or interfaces.
	 * @param  string
	 * @return void
	 */
	public function tryLoad($type)
	{
		$type = ltrim(strtolower($type), '\\');
		if (isset($this->list[$type])) {
			LimitedScope::load(NETTE_DIR . $this->list[$type]);
			self::$count++;
		}
	}

}
