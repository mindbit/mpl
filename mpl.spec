Summary:        Mindbit PHP Libraries
Name:           mpl
Version:        0.3
Release:        1%{dist}
License:        LGPL
BuildArch:		noarch
Packager:       Radu Rendec
Group:			System Environment/Libraries
Vendor:         Mindbit SRL
Source:			%{name}-%{version}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-root
Requires:		php php-mysql php-ldap mpl

%description
PHP libraries that provide a safe application environment and facilitate
integration with several frameworks such as Propel and SmartClient.

%prep
%setup -q -n %{name}

%build

%install
rm -rf $RPM_BUILD_ROOT

find \
	*.php \
	web \
	-type f -exec install -m 644 -D \{\} ${RPM_BUILD_ROOT}%{_datadir}/mpl/\{\} \;

%clean
rm -rf $RPM_BUILD_ROOT
rm -rf $RPM_BUILD_DIR/%{name}-%{version}

%files
%defattr(-,root,root)
%{_datadir}/mpl

%changelog
* Mon Jun 30 2010 Radu Rendec <radu.rendec@mindbit.ro> - 0.1-1
- Created spec file
