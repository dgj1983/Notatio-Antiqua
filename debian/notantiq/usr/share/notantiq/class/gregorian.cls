\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{gregorian}[2010/10/15 v4 for typesetting gregorian chant]
\ClassInfo{gregorian.cls}{V 4 typesetting gregorian chant with gregorio and LuaLaTeX}

% needed packages
\RequirePackage{kvoptions}



% the classes own options

\SetupKeyvalOptions{
  family=GRE,
  prefix=GRE@
}
\DeclareStringOption{initial}[ZallmanCaps]
\DeclareStringOption{staff}[19] %{}
\DeclareStringOption{font}[greciliae]
\DeclareStringOption{choralsign}[PfefferMediaeval]
% Implementing the option settings
% initial font
 \AtBeginDocument{\newfontfamily\initfamily{\GRE@initial}\def\greinitialformat#1{{\fontsize{32}{12}\selectfont\initfamily #1}}}
% size of staff and neumes
 \AtBeginDocument{\setgrefactor{\GRE@staff}\ClassInfo{\@currname}{Staff size: '\GRE@staff'}}
% gregorian font
\ifx\GRE@greciliae\setkeys{GRE}{font}{greciliae}\fi
\ifx\GRE@gregorio\setkeys{GRE}{font}{gregorio}\fi
\ifx\GRE@parmesan\setkeys{GRE}{font}{parmesan}\fi
 \AtBeginDocument{\setgregoriofont{\GRE@font}\ClassInfo{\@currname}{Gregorian Font: '\GRE@font'}}
% font for choral signs
\AtBeginDocument{%
    \newfontfamily\choralsignfont{\GRE@choralsign}%
    \def\grehighchoralsignstyle#1{{\tiny\selectfont\choralsignfont\itshape #1}}%
    \def\grelowchoralsignstyle#1{{\tiny\selectfont\choralsignfont\itshape #1}}%
}

% passing on of options to base class memoir
\DeclareDefaultOption{%
\ifx\CurrentOptionValue\relax
\ClassInfo{\@currname}{%
Option '\CurrentOption'\MessageBreak is passed to class 'memoir'%
}%
\expandafter\PassOptionsToClass\expandafter{\CurrentOption}{memoir}%
\else
\@unknownoptionerror
\fi
}
\ProcessKeyvalOptions*\relax
\LoadClass{memoir}

\AtEndOfClass{%
\flushbottom%
\setsecnumdepth{none}%
\chapterstyle{gregorian}%
\RequirePackage[protrusion=true,expansion]{microtype}
\RequirePackage{fontspec}%
\RequirePackage{longtable}%
\RequirePackage{xcolor}%
\RequirePackage{graphicx}%
\RequirePackage{paralist}%
\RequirePackage{multicol}%
\RequirePackage{wallpaper}%
\RequirePackage{pifont}%
\RequirePackage[ngerman]{babel}
\RequirePackage[german=guillemets]{csquotes}%
\RequirePackage{optimize-gabc}%
\RequirePackage{framed}%
}

% set some standards
\AtBeginDocument{%
\setlength\parindent{0pt}%
\setaboveinitialseparation{3pt}%
\def\gebet{}%
\def\guillemotleft{«}%
\def\guillemotright{»}%
\newcommand{\sources}[2]{%
\marginpar{%
\SingleSpacing%
\begin{tabular}{|l|}%
\hline%
\scriptsize #1\\%
\hline%
\scriptsize #2\\%
\hline%
\end{tabular}%
\DoubleSpacing%
}%
}%
}
\newcommand{\textsmcpit}[1]{\textit{\textsc{#1}}}
\newcommand{\aiintro}[2]{\gresetfirstannotation{\scriptsize #1}\gresetsecondannotation{\scriptsize #2}}
\AtEndOfClass{\renewcommand{\greabovelinestextstyle}{\scriptsize\itshape}}
\setsecheadstyle{\sffamily}%
\setsubsecheadstyle{\sffamily}%
\setbeforesecskip{1em}%
\setaftersecskip{0.5em}%
\setbeforesubsecskip{0.2em}%
\setaftersubsecskip{0.1em}%
\global\let\ifvtex\undefined
\newcommand{\verseindent}[1]{\begingroup\leftskip=1em\noindent #1 \par\endgroup}
 \def\ulin#1{\underline{#1}}
  \let\endgraph\endgraf
\makechapterstyle{gregorian}{%
\renewcommand{\chapterheadstart}{}
\renewcommand{\chapnamefont}{\normalfont\large\scshape}
\renewcommand{\chapnumfont}{\normalfont\large\scshape}
\renewcommand{\chaptitlefont}{\sffamily\large\scshape\centering}
\renewcommand{\printchaptername}{\chaptitlefont}
\renewcommand{\chapternamenum}{}
\renewcommand{\printchapternum}{}
\renewcommand{\afterchapternum}{}
\renewcommand{\afterchaptertitle}{\par\gresep{5}{50}\par}
% some macro translations in german for old version compatibility
\let\ueberinitiale\aiintro
\let\einzug\verseindent
}
