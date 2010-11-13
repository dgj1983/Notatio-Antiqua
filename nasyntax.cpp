/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software Foundation;
version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

/****************************************************************************
**
** Copyright (C) 2005-2007 Trolltech ASA. All rights reserved.
**
** This file is part of the example classes of the Qt Toolkit.
**
** This file may be used under the terms of the GNU General Public
** License version 2.0 as published by the Free Software Foundation
** and appearing in the file LICENSE.GPL included in the packaging of
** this file.  Please review the following information to ensure GNU
** General Public Licensing requirements will be met:
** http://www.trolltech.com/products/qt/opensource.html
**
** If you are unsure which license is appropriate for your use, please
** review the following information:
** http://www.trolltech.com/products/qt/licensing.html or contact the
** sales department at sales@trolltech.com.
**
** This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING THE
** WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
**
****************************************************************************/

#include <QtGui>

#include "nasyntax.h"

Highlighter::Highlighter(QTextDocument *parent)
    : QSyntaxHighlighter(parent)
{
    HighlightingRule rule;

    keywordFormat.setForeground(Qt::darkBlue);
    keywordFormat.setFontWeight(QFont::Bold);
    QStringList keywordPatterns;
    keywordPatterns << "\\blilypondfile\\b" <<"\\bdocumentclass\\b" <<"\\busepackage\\b" << "\\bdef\\b" <<"\\brenewcommand\\b" <<"\\bhskip\\b"
                    << "\\bbegin\\b" <<"\\bend\\b" << "\\bbook\\b" << "\\boccasion\\b" << "\\bname\\b"
                    << "\\bgabc-copyright\\b" << "\\bscore-copyright\\b" << "\\boffice-part\\b"
                    << "\\bmeter\\b" << "\\barranger\\b" << "\\bgabc-version\\b"
                    << "\\bauthor\\b" << "\\bdate\\b" << "\\bmanuscript\\b" << "\\bmanuscript-reference\\b"
                    << "\\bmanuscript-storage-place\\b" << "\\btranscriber\\b" << "\\btranscription-date\\b"
                    << "\\bgregoriotex-font\\b" << "\\bmode\\b" << "\\binitial-style\\b" << "\\buser-notes\\b"
                    << "\\bannotation\\b" << "\\bueberinitiale\\b" <<  "\\bcommentary\\b" << "\\bsources\\b" << "\\bAbar\\b";
    foreach (QString pattern, keywordPatterns) {
        rule.pattern = QRegExp(pattern);
        rule.format = keywordFormat;
        highlightingRules.append(rule);
    }

    classFormat.setFontWeight(QFont::Bold);
    classFormat.setForeground(Qt::darkMagenta);
    rule.pattern = QRegExp("\\bQ[A-Za-z]+\\b");
    rule.format = classFormat;
    highlightingRules.append(rule);

    singleLineCommentFormat.setForeground(Qt::darkGreen);
    rule.pattern = QRegExp("%%[^\n]*");
    rule.format = singleLineCommentFormat;
    highlightingRules.append(rule);

    gabcCodeFormat.setForeground(Qt::red);
    translationFormat.setForeground(Qt::darkYellow);

    quotationFormat.setForeground(Qt::darkGreen);
    rule.pattern = QRegExp("\".*\"");
    rule.format = quotationFormat;
    highlightingRules.append(rule);

    functionFormat.setFontWeight(QFont::Bold);
    functionFormat.setForeground(Qt::darkYellow);
    rule.pattern = QRegExp("\\b[*]+(?=\\()");
    rule.format = functionFormat;
    highlightingRules.append(rule);

    gabcCodeFormat.setForeground(Qt::darkRed);
    gabcCodeFormat.setFontWeight(QFont::Bold);
    gabcCodeStartExpression = QRegExp("\\(");
    gabcCodeEndExpression = QRegExp("\\)");

    spcharFormat.setForeground(Qt::blue);
    spcharFormat.setFontWeight(QFont::Bold);
    spcharStartExpression = QRegExp("\\<");
    spcharEndExpression = QRegExp("\\>");

    translationFormat.setForeground(Qt::darkYellow);
    translationFormat.setFontWeight(QFont::Normal);
    translationStartExpression = QRegExp("\\[");
    translationEndExpression = QRegExp("\\]");

}

void Highlighter::highlightBlock(const QString &text)
{
    foreach (HighlightingRule rule, highlightingRules) {
        QRegExp expression(rule.pattern);
        int index = text.indexOf(expression);
        while (index >= 0) {
            int length = expression.matchedLength();
            setFormat(index, length, rule.format);
            index = text.indexOf(expression, index + length);
        }
    }
    setCurrentBlockState(0);

    int startIndex = 0;
    if (previousBlockState() != 1)
        startIndex = text.indexOf(gabcCodeStartExpression);

    while (startIndex >= 0) {
        int endIndex = text.indexOf(gabcCodeEndExpression, startIndex);
        int gcLength;
        if (endIndex == -1) {
            setCurrentBlockState(1);
            gcLength = text.length() - startIndex;
        } else {
            gcLength = endIndex - startIndex
                            + gabcCodeEndExpression.matchedLength();
        }
        setFormat(startIndex, gcLength, gabcCodeFormat);
        startIndex = text.indexOf(gabcCodeStartExpression,
                                                startIndex + gcLength);
    }

    startIndex = 0;
    if (previousBlockState() != 1)
        startIndex = text.indexOf(translationStartExpression);

    while (startIndex >= 0) {
        int endIndex = text.indexOf(translationEndExpression, startIndex);
        int trLength;
        if (endIndex == -1) {
            setCurrentBlockState(1);
            trLength = text.length() - startIndex;
        } else {
            trLength = endIndex - startIndex
                            + translationEndExpression.matchedLength();
        }
        setFormat(startIndex, trLength, translationFormat);
        startIndex = text.indexOf(translationStartExpression,
                                                startIndex + trLength);
    }
    startIndex = 0;
    if (previousBlockState() != 1)
        startIndex = text.indexOf(spcharStartExpression);

    while (startIndex >= 0) {
        int endIndex = text.indexOf(spcharEndExpression, startIndex);
        int trLength;
        if (endIndex == -1) {
            setCurrentBlockState(1);
            trLength = text.length() - startIndex;
        } else {
            trLength = endIndex - startIndex
                            + spcharEndExpression.matchedLength();
        }
        setFormat(startIndex, trLength, spcharFormat);
        startIndex = text.indexOf(spcharStartExpression,
                                                startIndex + trLength);
    }
}
