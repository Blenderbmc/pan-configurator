<?php


// <editor-fold desc=" ***** Rule filters *****" defaultstate="collapsed" >

//                                              //
//                Zone Based Actions            //
//                                              //
RQuery::$defaultFilters['rule']['from']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        if( $object->isPbfRule() && !$object->isZoneBased() )
            return $object->from->hasInterface($value) === true;

        if( $object->isDoSRule() && !$object->isZoneBasedFrom() )
            return $object->from->hasInterface($value) === true;

        return $object->from->hasZone($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->from->parentCentralStore->find('!value!');"



);
RQuery::$defaultFilters['rule']['from']['operators']['has.only'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        if( $object->isPbfRule() && !$object->isZoneBased() )
            return $object->from->hasInterface($value) === true && $object->from->count() == 1;
        if( $object->isDoSRule() && !$object->isZoneBasedFrom() )
            return $object->from->hasInterface($value) === true && $object->from->count() == 1;

        return $object->from->count() == 1 && $object->from->hasZone($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->from->parentCentralStore->find('!value!');"
);

RQuery::$defaultFilters['rule']['to']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        if( $object->isPbfRule() )
            return false;

        if( $object->isDoSRule() && !$object->isZoneBasedTo() )
            return $object->to->hasInterface($value) === true;

        return $object->to->hasZone($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => function($object, $argument)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        if( $object->isPbfRule() )
            return false;

        return $object->to->parentCentralStore->find($argument);
    },
    'help' => 'returns TRUE if field TO is using zone mentionned in argument. Ie: "(to has Untrust)"'
);
RQuery::$defaultFilters['rule']['to']['operators']['has.only'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        if( $object->isPbfRule() )
            return false;

        if( $object->isDoSRule() && !$object->isZoneBasedFrom() )
            return $object->to->hasInterface($value) === true && $object->to->count() == 1;

        return $object->to->count() == 1 && $object->to->hasZone($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->to->parentCentralStore->find('!value!');"
);


RQuery::$defaultFilters['rule']['from']['operators']['has.regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        foreach($context->object->from->zones() as $zone )
        {
            $matching = preg_match($context->value, $zone->name());
            if( $matching === FALSE )
                derr("regular expression error on '{$context->value}'");
            if( $matching === 1 )
                return true;
        }
        return false;
    },
    'arg' => true,
);
RQuery::$defaultFilters['rule']['to']['operators']['has.regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->isPbfRule() )
            return false;

        foreach($context->object->to->zones() as $zone )
        {
            $matching = preg_match( $context->value, $zone->name() );
            if( $matching === FALSE )
                derr("regular expression error on '{$context->value}'");
            if( $matching === 1 )
                return true;
        }
        return false;
    },
    'arg' => true,
);

RQuery::$defaultFilters['rule']['from.count']['operators']['>,<,=,!'] = Array(
    'eval' => "\$object->from->count() !operator! !value!",
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['to.count']['operators']['>,<,=,!'] = Array(
    'eval' => "\$object->to->count() !operator! !value!",
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['from']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->from->isAny();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['to']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->isPbfRule() )
            return false;

        return $context->object->to->isAny();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

//                                              //
//                NAT Dst/Src Based Actions     //
//                                              //
RQuery::$defaultFilters['rule']['snathost']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        if (!$object->isNatRule()) return false;

        return $object->snathosts->has($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->owner->owner->addressStore->find('!value!');"

);
RQuery::$defaultFilters['rule']['dnathost']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value) {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        if (!$object->isNatRule()) return false;
        if ($object->dnathost === null) return false;

        return $object->dnathost === $value;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->owner->owner->addressStore->find('!value!');"
);

//                                              //
//                SNAT Based Actions            //
//                                              //
RQuery::$defaultFilters['rule']['snat']['operators']['is.static'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isNatRule() ) return false;
        if( !$context->object->sourceNatTypeIs_Static() ) return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['snat']['operators']['is.dynamic-ip'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isNatRule() ) return false;
        if( !$context->object->sourceNatTypeIs_Dynamic() ) return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['snat']['operators']['is.dynamic-ip-and-port'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isNatRule() )
            return false;

        if( !$context->object->sourceNatTypeIs_DIPP() )
            return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

//                                              //
//                SNAT interface Based Actions            //
//                                              //
RQuery::$defaultFilters['rule']['dst-interface']['operators']['is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isNatRule() )
            return false;

        return $context->object->hasDestinationInterface();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

//                                              //
//                Dst/Src Based Actions            //
//                                              //



RQuery::$commonFilters['src-dst']['xxx-is.fully.included.in.list'] = function(RuleRQueryContext $context, AddressRuleContainer $srcOrDst )
{
    $list = &$context->value;

    if( !isset($context->cachedIPMapping) )
    {
        $listMapping = new IP4Map();

        foreach( $list as $item )
            $listMapping->addMap(IP4Map::mapFromText($item), false);

        $listMapping->sortAndRecalculate();

        $context->cachedIP4Mapping = $listMapping;
    }
    else
        $listMapping = $context->cachedIP4Mapping;

    return $srcOrDst->getIP4Mapping()->includedInOtherMap($listMapping) == 1;
};

RQuery::$commonFilters['src-dst']['xxx-is.partially.included.in.list'] = function(RuleRQueryContext $context, AddressRuleContainer $srcOrDst )
{
    $list = &$context->value;

    if( !isset($context->cachedIPMapping) )
    {
        $listMapping = new IP4Map();

        foreach( $list as $item )
            $listMapping->addMap(IP4Map::mapFromText($item), false);

        $listMapping->sortAndRecalculate();

        $context->cachedIP4Mapping = $listMapping;
    }
    else
        $listMapping = $context->cachedIP4Mapping;

    return $srcOrDst->getIP4Mapping()->includedInOtherMap($listMapping) == 2;
};

RQuery::$commonFilters['src-dst']['xxx-is.partially.or.fully.included.in.list'] = function(RuleRQueryContext $context, AddressRuleContainer $srcOrDst )
{
    $list = &$context->value;

    /** @var IP4Map $lisMapping */

    if( !isset($context->cachedIPMapping) )
    {
        $listMapping = new IP4Map();

        foreach( $list as $item )
            $listMapping->addMap(IP4Map::mapFromText($item), false);

        $listMapping->sortAndRecalculate();

        $context->cachedIP4Mapping = $listMapping;
    }
    else
        $listMapping = $context->cachedIP4Mapping;

    return $srcOrDst->getIP4Mapping()->includedInOtherMap($listMapping) > 0;
};



RQuery::$defaultFilters['rule']['src']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        return $object->source->has($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->source->parentCentralStore->find('!value!');"

);
RQuery::$defaultFilters['rule']['src']['operators']['has.only'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        return $object->source->count() == 1 && $object->source->has($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->source->parentCentralStore->find('!value!');"
);
RQuery::$defaultFilters['rule']['src']['operators']['has.recursive'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        return $object->source->hasObjectRecursive($value, false) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->source->parentCentralStore->find('!value!');"
);
RQuery::$defaultFilters['rule']['src']['operators']['has.recursive.regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $members = $context->object->source->membersExpanded(true);

        foreach( $members as $member)
        {
            $matching = preg_match($context->value, $member->name());
            if( $matching === FALSE )
                derr("regular expression error on '{$context->value}'");
            if( $matching === 1 )
                return true;
        }
        return false;
    },
    'arg' => true
);
RQuery::$defaultFilters['rule']['dst']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule  $object */
        return $object->destination->has($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->destination->parentCentralStore->find('!value!');"

);
RQuery::$defaultFilters['rule']['dst']['operators']['has.only'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        return $object->destination->count() == 1 && $object->destination->has($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->destination->parentCentralStore->find('!value!');"
);
RQuery::$defaultFilters['rule']['dst']['operators']['has.recursive'] = Array(
    'eval' => '$object->destination->hasObjectRecursive(!value!, false) === true',
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->destination->parentCentralStore->find('!value!');"
);
RQuery::$defaultFilters['rule']['dst']['operators']['has.recursive.regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $members = $context->object->destination->membersExpanded(true);

        foreach( $members as $member)
        {
            $matching = preg_match($context->value, $member->name());
            if( $matching === FALSE )
                derr("regular expression error on '{$context->value}'");
            if( $matching === 1 )
                return true;
        }
        return false;
    },
    'arg' => true
);
RQuery::$defaultFilters['rule']['src']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->source->count() == 0;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['dst']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->destination->count() == 0;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['src']['operators']['is.negated'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->isNatRule() )
            return false;

        return $context->object->sourceIsNegated();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['dst']['operators']['is.negated'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->isNatRule() )
            return false;

        return $context->object->destinationIsNegated();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['src']['operators']['included-in.full'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->source->includedInIP4Network($context->value) == 1;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['src']['operators']['included-in.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->source->includedInIP4Network($context->value) == 2;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['src']['operators']['included-in.full.or.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->source->includedInIP4Network($context->value) > 0;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['src']['operators']['includes.full'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->source->includesIP4Network($context->value) == 1;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['src']['operators']['includes.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->source->includesIP4Network($context->value) == 2;
    },
    'arg' => true
,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['src']['operators']['includes.full.or.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->source->includesIP4Network($context->value) > 0;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['src']['operators']['is.fully.included.in.list'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $f = RQuery::$commonFilters['src-dst']['xxx-is.fully.included.in.list'];
        return $f($context, $context->object->source);
    },
    'arg' => true,
    'argType' => 'commaSeparatedList'
);
RQuery::$defaultFilters['rule']['src']['operators']['is.partially.or.fully.included.in.list'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $f = RQuery::$commonFilters['src-dst']['xxx-is.partially.or.fully.included.in.list'];
        return $f($context, $context->object->source);
    },
    'arg' => true,
    'argType' => 'commaSeparatedList'
);
RQuery::$defaultFilters['rule']['src']['operators']['is.partially.included.in.list'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $f = RQuery::$commonFilters['src-dst']['xxx-is.partially.included.in.list'];
        return $f($context, $context->object->source);
    },
    'arg' => true,
    'argType' => 'commaSeparatedList'
);

RQuery::$defaultFilters['rule']['dst']['operators']['included-in.full'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->destination->includedInIP4Network($context->value) == 1;
    },
    'arg' => true,
    'argDesc' => 'ie: 192.168.0.0/24 | 192.168.50.10/32 | 192.168.50.10 | 10.0.0.0-10.33.0.0',
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['dst']['operators']['included-in.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->destination->includedInIP4Network($context->value) == 2;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['dst']['operators']['included-in.full.or.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->destination->includedInIP4Network($context->value) > 0;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['dst']['operators']['includes.full'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->destination->includesIP4Network($context->value) == 1;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['dst']['operators']['includes.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->destination->includesIP4Network($context->value) == 2;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['dst']['operators']['includes.full.or.partial'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->destination->includesIP4Network($context->value) > 0;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1.1.1.1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['src']['operators']['has.from.query'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->source->count() == 0 )
            return false;

        if( $context->value === null || !isset($context->nestedQueries[$context->value]) )
            derr("cannot find nested query called '{$context->value}'");

        $errorMessage = '';

        if( !isset($context->cachedSubRQuery) )
        {
            $rQuery = new RQuery('address');
            if( $rQuery->parseFromString($context->nestedQueries[$context->value], $errorMessage) === false )
                derr('nested query execution error : '.$errorMessage);
            $context->cachedSubRQuery = $rQuery;
        }
        else
            $rQuery = $context->cachedSubRQuery;

        foreach( $context->object->source->all() as $member )
        {
            if( $rQuery->matchSingleObject(Array('object' => $member, 'nestedQueries' => &$context->nestedQueries)) )
                return true;
        }

        return false;
    },
    'arg' => true
);
RQuery::$defaultFilters['rule']['dst']['operators']['has.from.query'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->destination->count() == 0 )
            return false;

        if( $context->value === null || !isset($context->nestedQueries[$context->value]) )
            derr("cannot find nested query called '{$context->value}'");

        $errorMessage = '';

        if( !isset($context->cachedSubRQuery) )
        {
            $rQuery = new RQuery('address');
            if( $rQuery->parseFromString($context->nestedQueries[$context->value], $errorMessage) === false )
                derr('nested query execution error : '.$errorMessage);
            $context->cachedSubRQuery = $rQuery;
        }
        else
            $rQuery = $context->cachedSubRQuery;

        foreach( $context->object->destination->all() as $member )
        {
            if( $rQuery->matchSingleObject(Array('object' => $member, 'nestedQueries' => &$context->nestedQueries)) )
                return true;
        }

        return false;
    },
    'arg' => true
);
RQuery::$defaultFilters['rule']['src']['operators']['has.recursive.from.query'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->source->count() == 0 )
            return false;

        if( $context->value === null || !isset($context->nestedQueries[$context->value]) )
            derr("cannot find nested query called '{$context->value}'");

        $errorMessage = '';

        if( !isset($context->cachedSubRQuery) )
        {
            $rQuery = new RQuery('address');
            if( $rQuery->parseFromString($context->nestedQueries[$context->value], $errorMessage) === false )
                derr('nested query execution error : '.$errorMessage);
            $context->cachedSubRQuery = $rQuery;
        }
        else
            $rQuery = $context->cachedSubRQuery;

        foreach( $context->object->source->membersExpanded() as $member )
        {
            if( $rQuery->matchSingleObject(Array('object' => $member, 'nestedQueries' => &$context->nestedQueries)) )
                return true;
        }

        return false;
    },
    'arg' => true
);
RQuery::$defaultFilters['rule']['dst']['operators']['has.recursive.from.query'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->destination->count() == 0 )
            return false;

        if( $context->value === null || !isset($context->nestedQueries[$context->value]) )
            derr("cannot find nested query called '{$context->value}'");

        $errorMessage = '';

        if( !isset($context->cachedSubRQuery) )
        {
            $rQuery = new RQuery('address');
            if( $rQuery->parseFromString($context->nestedQueries[$context->value], $errorMessage) === false )
                derr('nested query execution error : '.$errorMessage);
            $context->cachedSubRQuery = $rQuery;
        }
        else
            $rQuery = $context->cachedSubRQuery;

        foreach( $context->object->destination->all() as $member )
        {
            if( $rQuery->matchSingleObject(Array('object' => $member, 'nestedQueries' => &$context->nestedQueries)) )
                return true;
        }

        return false;
    },
    'arg' => true
);

RQuery::$defaultFilters['rule']['dst']['operators']['is.fully.included.in.list'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $f = RQuery::$commonFilters['src-dst']['xxx-is.fully.included.in.list'];
        return $f($context, $context->object->destination);
    },
    'arg' => true,
    'argType' => 'commaSeparatedList'
);
RQuery::$defaultFilters['rule']['dst']['operators']['is.partially.or.fully.included.in.list'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $f = RQuery::$commonFilters['src-dst']['xxx-is.partially.or.fully.included.in.list'];
        return $f($context, $context->object->destination);
    },
    'arg' => true,
    'argType' => 'commaSeparatedList'
);
RQuery::$defaultFilters['rule']['dst']['operators']['is.partially.included.in.list'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $f = RQuery::$commonFilters['src-dst']['xxx-is.partially.included.in.list'];
        return $f($context, $context->object->destination);
    },
    'arg' => true,
    'argType' => 'commaSeparatedList'
);


//                                                //
//                Tag Based filters              //
//                                              //
RQuery::$defaultFilters['rule']['tag']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        return $object->tags->hasTag($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->tags->parentCentralStore->find('!value!');",
    'ci' => Array(
        'fString' => '(%PROP% test.tag)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['tag']['operators']['has.nocase'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->tags->hasTag($context->value, false) === true;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% test.tag)',
        'input' => 'input/panorama-8.0.xml'
    )

);
RQuery::$defaultFilters['rule']['tag']['operators']['has.regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        foreach($context->object->tags->tags() as $tag )
        {
            $matching = preg_match( $context->value, $tag->name() );
            if( $matching === FALSE )
                derr("regular expression error on '{$context->value}'");
            if( $matching === 1 )
                return true;
        }

        return false;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% /test-/)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['tag.count']['operators']['>,<,=,!'] = Array(
    'eval' => "\$object->tags->count() !operator! !value!",
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% 1)',
        'input' => 'input/panorama-8.0.xml'
    )
);



//                                              //
//          Application properties              //
//                                              //
RQuery::$defaultFilters['rule']['app']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        return ($rule->isSecurityRule() || $rule->isQoSRule()) && $rule->apps->isAny();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['app']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        return $object->apps->hasApp($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->apps->parentCentralStore->find('!value!');",
);
RQuery::$defaultFilters['rule']['app']['operators']['has.nocase'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        return ($rule->isSecurityRule() || $rule->isQoSRule()) && $rule->apps->hasApp($context->value, false) === true;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% icmp)',
        'input' => 'input/panorama-8.0.xml'
    )
    //'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->tags->parentCentralStore->find('!value!');"
);


//                                              //
//          Services properties                 //
//                                              //
RQuery::$defaultFilters['rule']['service']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( $rule->isNatRule() )
            return $rule->service === null;

        return $rule->services->isAny();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['service']['operators']['is.application-default'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->isSecurityRule() && $context->object->services->isApplicationDefault();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['service']['operators']['has'] = Array(
    'eval' => function($object, &$nestedQueries, $value)
    {
        /** @var Rule|SecurityRule|NatRule|DecryptionRule|AppOverrideRule|CaptivePortalRule|PbfRule|QoSRule|DoSRule $object */
        return $object->services->has($value) === true;
    },
    'arg' => true,
    'argObjectFinder' => "\$objectFind=null;\n\$objectFind=\$object->services->parentCentralStore->find('!value!');"
);
RQuery::$defaultFilters['rule']['service']['operators']['has.regex'] = Array(
    'Function' => function(RuleRQueryContext $context)
    {
        $rule = $context->object;

        if( $rule->isNatRule() )
        {
            if( $rule->service === null )
                return false;
            $matching = preg_match($context->value, $rule->service->name() );
            if( $matching === FALSE )
                derr("regular expression error on '{$context->value}'");
            if( $matching === 1 )
                return true;
            return false;
        }

        foreach( $rule->services->all() as $service )
        {
            $matching = preg_match($context->value, $service->name() );
            if( $matching === FALSE )
                derr("regular expression error on '{$context->value}'");
            if( $matching === 1 )
                return true;
        }

        return false;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% /tcp-/)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['service']['operators']['has.recursive'] = Array(
    'Function' => function(RuleRQueryContext $context)
    {
        $rule = $context->object;
        /** @var Service|ServiceGroup $value */
        $value = $context->value;

        if( $rule->isNatRule() )
        {
            if( $rule->service === null )
                return false;

            if( $rule->service->name() == $value )
                return true;

            if( !$rule->service->isGroup() )
                return false;

            return $rule->service->hasNamedObjectRecursive($value);
        }

        return $rule->services->hasNamedObjectRecursive($value);
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% tcp-80)',
        'input' => 'input/panorama-8.0.xml'
    )
);


//                                              //
//                SecurityProfile properties    //
//                                              //
RQuery::$defaultFilters['rule']['secprof']['operators']['not.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return false;

        return $context->object->securityProfileIsBlank();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return false;

        return !$context->object->securityProfileIsBlank();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
// @TODO Remove later / DEPRECATED
RQuery::$defaultFilters['rule']['secprof']['operators']['is.profile'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        return $rule->isSecurityRule()
            && !$context->object->securityProfileIsBlank()
            && $context->object->securityProfileType() == "profile";
    },
    'arg' => false,
    'deprecated' => 'this filter "secprof is.profile" is deprecated, you should use "secprof type.is.profile" instead!',
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['type.is.profile'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        return $rule->isSecurityRule()
            && !$context->object->securityProfileIsBlank()
            && $context->object->securityProfileType() == "profile";
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['is.group'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        return $rule->isSecurityRule() && $context->object->isSecurityRule()
            && !$context->object->securityProfileIsBlank()
            && $context->object->securityProfileType() == "group";
    },
    'arg' => false,
    'deprecated' => 'this filter "secprof is.profile" is deprecated, you should use "secprof type.is.profile" instead!',
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
// @TODO Remove later / DEPRECATED
RQuery::$defaultFilters['rule']['secprof']['operators']['type.is.group'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        return $rule->isSecurityRule() && $context->object->isSecurityRule()
            && !$context->object->securityProfileIsBlank()
            && $context->object->securityProfileType() == "group";
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['group.is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        return $rule->isSecurityRule()
            && $rule->securityProfileType() == "group"
            && $rule->securityProfileGroup() == $context->value;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% secgroup-production)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['av-profile.is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();
        if( !isset($profiles['virus']) )
            return false;

        return $profiles['virus'] == $context->value;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% av-production)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['as-profile.is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();
        if( !isset($profiles['spyware']) )
            return false;

        return $profiles['spyware'] == $context->value;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% as-production)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['url-profile.is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();
        if( !isset($profiles['url-filtering']) )
            return false;

        return $profiles['url-filtering'] == $context->value;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% url-production)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['wf-profile.is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();
        if( !isset($profiles['wildfire-analysis']) )
            return false;

        return $profiles['wildfire-analysis'] == $context->value;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% wf-production)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['vuln-profile.is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();
        if( !isset($profiles['vulnerability']) )
            return false;

        return $profiles['vulnerability'] == $context->value;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% vuln-production)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['av-profile.is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();

        return isset($profiles['virus']);
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['as-profile.is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();

        return isset($profiles['spyware']);
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['url-profile.is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();

        return isset($profiles['url-filtering']);
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['wf-profile.is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();

        return isset($profiles['wildfire-analysis']);
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['secprof']['operators']['vuln-profile.is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->securityProfileIsBlank() )
            return false;

        if( $rule->securityProfileType() == "group" )
            return false;

        $profiles = $rule->securityProfiles();

        return isset($profiles['vulnerability']);
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

//                                              //
//                Other properties              //
//                                              //
RQuery::$defaultFilters['rule']['action']['operators']['is.deny'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->isSecurityRule() && $context->object->actionIsDeny();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['action']['operators']['is.negative'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return false;
        return $context->object->actionIsNegative();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['action']['operators']['is.allow'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return false;
        return $context->object->actionIsAllow();
    },
    'arg' => false
);
RQuery::$defaultFilters['rule']['log']['operators']['at.start'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return false;
        return $context->object->logStart();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['log']['operators']['at.end'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return false;
        return $context->object->logEnd();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['logprof']['operators']['is.set'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;

        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->logSetting() === null || $rule->logSetting() == '' )
            return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['logprof']['operators']['is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( !$rule->isSecurityRule() )
            return false;

        if( $rule->logSetting() === null )
            return false;

        if( $rule->logSetting() == $context->value )
            return true;

        return false;
    },
    'arg' => true,
    'help' => 'return true if Log Forwarding Profile is the one specified in argument',
    'ci' => Array(
        'fString' => '(%PROP%  log_to_panorama)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['rule']['operators']['is.prerule'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->isPreRule();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['rule']['operators']['is.postrule'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->isPostRule();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['rule']['operators']['is.disabled'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->isDisabled();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['rule']['operators']['is.dsri'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return false;
        return $context->object->isDSRIEnabled();
    },
    'arg' => false,
    'help' => 'return TRUE if Disable Server Response Inspection has been enabled'
);
RQuery::$defaultFilters['rule']['rule']['operators']['is.bidir.nat'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isNatRule() )
            return false;

        return $context->object->isBiDirectional();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['rule']['operators']['has.source.nat'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isNatRule() )
            return false;

        if( $context->object->sourceNatTypeIs_None() )
            return true;

        return false;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['rule']['operators']['has.destination.nat'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isNatRule() )
            return false;

        if( $context->object->destinationNatIsEnabled() )
            return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['rule']['operators']['is.universal'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( !$context->object->isSecurityRule() )
            return true;

        if( $context->object->type() != 'universal' )
            return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['rule']['operators']['is.intrazone'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->owner->owner->version < 61 )
            return false;

        if( !$context->object->isSecurityRule() )
            return false;

        if( $context->object->type() != 'intrazone' )
            return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['rule']['operators']['is.interzone'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        if( $context->object->owner->owner->version < 61 )
            return false;

        if( !$context->object->isSecurityRule() )
            return false;

        if( $context->object->type() != 'interzone' )
            return false;

        return true;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['location']['operators']['is'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $owner = $context->object->owner->owner;
        if( strtolower($context->value) == 'shared' )
        {
            if( $owner->isPanorama() )
                return true;
            if( $owner->isFirewall() )
                return true;
            return false;
        }
        if( strtolower($context->value) == strtolower($owner->name()) )
            return true;

        return false;
    },
    'arg' => true,
    'help' => 'returns TRUE if object location (shared/device-group/vsys name) matches the one specified in argument',
    'ci' => Array(
        'fString' => '(%PROP%  Datacenter)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['location']['operators']['regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $name = $context->object->getLocationString();
        $matching = preg_match($context->value, $name);
        if( $matching === FALSE )
            derr("regular expression error on '{$context->value}'");
        if( $matching === 1 )
            return true;
        return false;
    },
    'arg' => true,
    'help' => 'returns TRUE if object location (shared/device-group/vsys name) matches the regular expression specified in argument',
    'ci' => Array(
        'fString' => '(%PROP%  /DC/)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['rule']['operators']['is.unused.fast'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $object = $context->object;

        if( !$object->isSecurityRule() && !$object->isNatRule() )
            derr("unsupported filter : rule type " . $object->ruleNature() . " is not supported yet. ".$object->toString());

        $unused_flag = 'unused'.$object->ruleNature();
        $rule_base = $object->ruleNature();

        $sub = $object->owner->owner;
        if( !$sub->isVirtualSystem() && !$sub->isDeviceGroup() )
            derr("this is filter is only supported on non Shared rules ".$object->toString());

        $connector = findConnector($sub);

        if( $connector === null )
            derr("this filter is available only from API enabled PANConf objects");

        if( !isset($sub->apiCache) )
            $sub->apiCache = Array();

        // caching results for speed improvements
        if( !isset($sub->apiCache[$unused_flag]) )
        {
            $sub->apiCache[$unused_flag] = Array();

            $apiCmd = '<show><running><rule-use><rule-base>' . $rule_base . '</rule-base><type>unused</type><vsys>' . $sub->name() . '</vsys></rule-use></running></show>';

            if( $sub->isVirtualSystem() )
            {
                $apiResult = $connector->sendCmdRequest($apiCmd);

                $rulesXml = DH::findXPath('/result/rules/entry', $apiResult);
                for ($i = 0; $i < $rulesXml->length; $i++)
                {
                    $ruleName = $rulesXml->item($i)->textContent;
                    $sub->apiCache[$unused_flag][$ruleName] = $ruleName;
                }
            }
            else
            {
                $devices = $sub->getDevicesInGroup();
                $firstLoop = true;

                foreach($devices as $device)
                {
                    $newConnector = new PanAPIConnector($connector->apihost, $connector->apikey, 'panos-via-panorama', $device['serial']);
                    $newConnector->setShowApiCalls($connector->showApiCalls);
                    $tmpCache = Array();

                    foreach($device['vsyslist'] as $vsys)
                    {
                        $apiCmd = '<show><running><rule-use><rule-base>' . $rule_base . '</rule-base><type>unused</type><vsys>' . $vsys . '</vsys></rule-use></running></show>';
                        $apiResult = $newConnector->sendCmdRequest($apiCmd);

                        $rulesXml = DH::findXPath('/result/rules/entry', $apiResult);

                        for ($i = 0; $i < $rulesXml->length; $i++)
                        {
                            $ruleName = $rulesXml->item($i)->textContent;
                            if( $firstLoop )
                                $sub->apiCache[$unused_flag][$ruleName] = $ruleName;
                            else
                            {
                                $tmpCache[$ruleName] = $ruleName;
                            }
                        }

                        if( !$firstLoop )
                        {
                            foreach( $sub->apiCache[$unused_flag] as $unusedEntry )
                            {
                                if( !isset($tmpCache[$unusedEntry]) )
                                    unset($sub->apiCache[$unused_flag][$unusedEntry]);
                            }
                        }

                        $firstLoop = false;
                    }
                }
            }
        }

        if( isset($sub->apiCache[$unused_flag][$object->name()]) )
            return true;

        return false;
    },
    'arg' => false
);


RQuery::$defaultFilters['rule']['name']['operators']['eq'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {   return $context->object->name() == $context->value;
    },
    'arg' => true,
    'help' => 'returns TRUE if rule name matches the one specified in argument',
    'ci' => Array(
        'fString' => '(%PROP%  rule1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['name']['operators']['regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $matching = preg_match($context->value, $context->object->name());
        if( $matching === FALSE )
            derr("regular expression error on '{$context->value}'");
        if( $matching === 1 )
            return true;
        return false;
    },
    'arg' => true,
    'help' => 'returns TRUE if rule name matches the regular expression provided in argument',
    'ci' => Array(
        'fString' => '(%PROP%  /^example/)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['name']['operators']['eq.nocase'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return strtolower($context->object->name()) == strtolower($context->value);
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP%  rule1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['name']['operators']['contains'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return stripos($context->object->name(), $context->value) !== false;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP%  searchME)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['name']['operators']['is.in.file'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $object = $context->object;

        if( !isset($context->cachedList) )
        {
            $text = file_get_contents($context->value);

            if( $text === false )
                derr("cannot open file '{$context->value}");

            $lines = explode("\n", $text);
            foreach( $lines as  $line)
            {
                $line = trim($line);
                if(strlen($line) == 0)
                    continue;
                $list[$line] = true;
            }

            $context->cachedList = &$list;
        }
        else
            $list = &$context->cachedList;

        return isset($list[$object->name()]);
    },
    'arg' => true,
    'help' => 'returns TRUE if rule name matches one of the names found in text file provided in argument'
);

//                                              //
//                UserID properties             //
//                                              //
RQuery::$defaultFilters['rule']['user']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( $rule->isDecryptionRule() )
            return false;
        if( $rule->isNatRule() )
            return false;

        return $rule->userID_IsAny();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['user']['operators']['is.known'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( $rule->isDecryptionRule() )
            return false;
        if( $rule->isNatRule() )
            return false;

        return $rule->userID_IsKnown();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['user']['operators']['is.unknown'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( $rule->isDecryptionRule() )
            return false;
        if( $rule->isNatRule() )
            return false;

        return $rule->userID_IsUnknown();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['rule']['user']['operators']['is.prelogon'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $rule = $context->object;
        if( $rule->isDecryptionRule() )
            return false;
        if( $rule->isNatRule() )
            return false;

        return $rule->userID_IsPreLogon();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);


RQuery::$defaultFilters['rule']['target']['operators']['is.any'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        return $context->object->target_isAny();
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['rule']['target']['operators']['has'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $vsys = null;

        $ex = explode('/', $context->value);

        if( count($ex) > 2 )
            derr("unsupported syntax for target: '{$context->value}'. Expected something like : 00F120CCC/vsysX");

        if( count($ex) == 1 )
            $serial = $context->value;
        else
        {
            $serial = $ex[0];
            $vsys = $ex[1];
        }

        return $context->object->target_hasDeviceAndVsys($serial, $vsys);
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP%  00YC25C)',
        'input' => 'input/panorama-8.0.xml'
    )
);


RQuery::$defaultFilters['rule']['description']['operators']['is.empty'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $desc = $context->object->description();

        if( $desc === null || strlen($desc) == 0 )
            return true;

        return false;
    },
    'arg' => false,
    'ci' => Array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);


RQuery::$defaultFilters['rule']['description']['operators']['regex'] = Array(
    'Function' => function(RuleRQueryContext $context )
    {
        $matching = preg_match($context->value, $context->object->description());
        if( $matching === FALSE )
            derr("regular expression error on '{$context->value}'");
        if( $matching === 1 )
            return true;
        return false;
    },
    'arg' => true,
    'ci' => Array(
        'fString' => '(%PROP% /input a string here/)',
        'input' => 'input/panorama-8.0.xml'
    )
);

// </editor-fold>

