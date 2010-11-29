cmake_minimum_required( VERSION 2.6 )
set ( CMAKE_BUILD_TYPE Release )
add_definitions ( -Wall )
find_package ( Qt4 REQUIRED )
include ( ${QT_USE_FILE} )
include_directories (
	${CMAKE_SOURCE_DIR} ${CMAKE_CURRENT_BINARY_DIR}
	${QT_QTCORE_INCLUDE_DIR} ${QT_QTGUI_INCLUDE_DIR}
	.)
set ( Notatio-Antiqua_HDRS
	
	)

set ( Notatio-Antiqua_SRCS
	main.cpp
	naclefselect.cpp
	naheaderwizard.cpp
	nahelp.cpp
	namdi.cpp
	naprog.cpp
	nasettings.cpp
	nasyntax.cpp
	)

set ( Notatio-Antiqua_UIS
	naclefselect.ui
	naheaderwizard.ui
	nahelp.ui
	naprog.ui
	nasettings.ui
	)
QT4_WRAP_UI(UIS ${Notatio-Antiqua_UIS})

set ( Notatio-Antiqua_RSCS
	naprog.qrc
	)
QT4_ADD_RESOURCES(RSCS ${Notatio-Antiqua_RSCS})

set ( Notatio-Antiqua_TRS
	NaProg-de.ts
	NaProg-en.ts
	NaProg-fr.ts
	)
QT4_ADD_TRANSLATION(TRS ${Notatio-Antiqua_TRS})

set ( Notatio-Antiqua_MOCS
	naclefselect.h
	naheaderwizard.h
	nahelp.h
	namdi.h
	naprog.h
	nasettings.h
	nasyntax.h
	)
QT4_WRAP_CPP(MOCS ${Notatio-Antiqua_MOCS})

add_executable ( Notatio-Antiqua ${Notatio-Antiqua_SRCS} ${UIS} ${RSCS} ${TRS} ${MOCS} )
target_link_libraries ( Notatio-Antiqua  ${QT_QTCORE_LIBRARY} ${QT_QTGUI_LIBRARY} )
